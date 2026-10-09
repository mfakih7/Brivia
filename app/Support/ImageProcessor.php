<?php

namespace App\Support;

use App\Models\Media;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Validates and re-encodes approved staff images. Only JPEG/PNG/WebP are accepted; the
 * real format is sniffed from file bytes, dimensions are checked BEFORE decoding, and every
 * output is a freshly encoded WebP (metadata is not carried over). Paths are random and
 * generated server-side; the client file name is stored only as display metadata.
 */
class ImageProcessor
{
    /** Minimum useful dimensions per role [width, height]. */
    public const MIN_DIMENSIONS = [
        'cover' => [1200, 675],
        'gallery' => [800, 450],
        'portrait' => [400, 400],
    ];

    private const ALLOWED_TYPES = [IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_PNG => 'image/png', IMAGETYPE_WEBP => 'image/webp'];

    public function store(UploadedFile $file, string $role, ?string $altText = null, string $field = 'image'): Media
    {
        $fail = fn (string $message) => throw ValidationException::withMessages([$field => $message]);
        $config = config('brivia.media');

        if (! $file->isValid()) {
            $fail('The upload failed. Images must be 5 MB or smaller.');
        }

        if ($file->getSize() > $config['max_kilobytes'] * 1024) {
            $fail('Images must be 5 MB or smaller.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) || preg_match('/\.(php\d?|phtml|phar|html?|svg|js)\./i', $file->getClientOriginalName())) {
            $fail('Only JPEG, PNG or WebP images are allowed.');
        }

        $info = @getimagesize($file->getRealPath());
        if ($info === false || ! isset(self::ALLOWED_TYPES[$info[2]])) {
            $fail('The file is not a valid JPEG, PNG or WebP image.');
        }

        $sniffed = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        if ($sniffed !== self::ALLOWED_TYPES[$info[2]]) {
            $fail('The file contents do not match an allowed image type.');
        }

        [$width, $height] = $info;
        if ($width > $config['max_dimension'] || $height > $config['max_dimension'] || $width * $height > $config['max_pixels']) {
            $fail('The image is too large. Maximum 6000 × 6000 pixels and 25 megapixels.');
        }

        [$minW, $minH] = self::MIN_DIMENSIONS[$role] ?? [200, 200];
        if ($width < $minW || $height < $minH) {
            $fail("The image is too small for this use. Minimum {$minW} × {$minH} pixels.");
        }

        $previousLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $source = $this->decode($file->getRealPath(), $info[2]);
            if (! $source) {
                $fail('The image could not be decoded.');
            }

            return $this->encodeAndPersist($source, $width, $height, $file->getClientOriginalName(), $altText);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            $fail('The image could not be processed. Try a different file.');
        } finally {
            ini_set('memory_limit', $previousLimit);
        }
    }

    private function decode(string $path, int $type): GdImage|false
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
        };
    }

    private function encodeAndPersist(GdImage $source, int $width, int $height, string $originalName, ?string $altText): Media
    {
        $disk = Storage::disk(config('brivia.media.disk'));
        $directory = 'media/'.now()->format('Y/m').'/'.Str::lower(Str::random(32));
        $variants = [];
        $totalBytes = 0;

        $widths = collect(config('brivia.media.variant_widths'))->filter(fn ($w) => $w < $width)->push(min($width, 2400))->unique()->sort()->values();

        foreach ($widths as $targetWidth) {
            $targetHeight = (int) round($height * ($targetWidth / $width));
            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            ob_start();
            imagewebp($canvas, null, 80);
            $bytes = ob_get_clean();
            imagedestroy($canvas);

            $path = "{$directory}/{$targetWidth}.webp";
            $disk->put($path, $bytes, ['visibility' => 'public']);
            $variants[(string) $targetWidth] = $path;
            $totalBytes = strlen($bytes);
        }

        imagedestroy($source);

        $largest = (int) $widths->last();
        $media = new Media(['alt_text' => $altText ? mb_substr(trim($altText), 0, 240) : null]);
        $media->forceFill([
            'disk' => config('brivia.media.disk'),
            'storage_path' => $variants[(string) $largest],
            'variants' => $variants,
            'mime_type' => 'image/webp',
            'size_bytes' => $totalBytes,
            'width' => $largest,
            'height' => (int) round($height * ($largest / $width)),
            'original_name' => mb_substr(basename($originalName), 0, 255),
            'uploaded_by' => Auth::id(),
        ])->save();

        return $media;
    }

    /** Deletes files and the row for media that nothing references anymore. */
    public function deleteIfUnreferenced(Media $media): bool
    {
        if ($media->isReferenced()) {
            return false;
        }

        Storage::disk($media->disk)->delete($media->allPaths());
        $media->delete();

        return true;
    }
}

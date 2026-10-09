<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Media> */
class MediaFactory extends Factory
{
    public function definition(): array
    {
        $dir = 'media/test/'.Str::random(16);

        return [
            'disk' => 'public',
            'storage_path' => $dir.'/1600.webp',
            'variants' => ['480' => $dir.'/480.webp', '960' => $dir.'/960.webp', '1600' => $dir.'/1600.webp'],
            'mime_type' => 'image/webp',
            'size_bytes' => 12345,
            'width' => 1600,
            'height' => 1000,
            'original_name' => 'example.jpg',
            'alt_text' => 'Example image',
        ];
    }
}

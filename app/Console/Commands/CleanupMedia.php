<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Support\ImageProcessor;
use Illuminate\Console\Command;

/** Controlled cleanup of replaced/removed images. Only database-known paths are ever deleted. */
class CleanupMedia extends Command
{
    protected $signature = 'brivia:media-cleanup {--older-than=60 : Minutes since upload before an unreferenced image is removed}';

    protected $description = 'Delete unreferenced media files created by image replacement or removal';

    public function handle(ImageProcessor $images): int
    {
        $removed = 0;

        Media::where('created_at', '<', now()->subMinutes((int) $this->option('older-than')))
            ->orderBy('id')
            ->each(function (Media $media) use ($images, &$removed) {
                if ($images->deleteIfUnreferenced($media)) {
                    $removed++;
                }
            });

        $this->info("Removed {$removed} unreferenced image(s).");

        return self::SUCCESS;
    }
}

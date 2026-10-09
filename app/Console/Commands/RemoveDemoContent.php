<?php

namespace App\Console\Commands;

use App\Models\DemoRecord;
use App\Models\Media;
use App\Models\Service;
use App\Support\ImageProcessor;
use App\Support\PublicContentCache;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Removes ONLY content registered by DemoContentSeeder. Records referenced by real data
 * (e.g. an enquiry) are kept and reported. Singleton fields are restored only if they still
 * hold the demo value. Demo images are deleted only when nothing references them.
 */
class RemoveDemoContent extends Command
{
    protected $signature = 'brivia:demo-content:remove {--force : Skip the confirmation prompt}';

    protected $description = 'Remove development-only demo content created by DemoContentSeeder (local/testing only)';

    /** Child-first order so dependencies are released before parents. */
    private const ORDER = ['project', 'package', 'consultation_type', 'team_member', 'service', 'project_category'];

    public function handle(ImageProcessor $images): int
    {
        if (! DemoRecord::isAllowedEnvironment()) {
            $this->error('Refusing to run: demo cleanup is allowed only when APP_ENV is local or testing.');

            return self::FAILURE;
        }

        $count = DemoRecord::count();
        if ($count === 0) {
            $this->info('No demo content is registered. Nothing to remove.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Remove {$count} registered demo item(s)? Real content is not affected.")) {
            $this->line('Cancelled.');

            return self::FAILURE;
        }

        $removed = 0;
        $kept = [];

        foreach (self::ORDER as $type) {
            foreach (DemoRecord::where('record_type', $type)->orderByDesc('id')->get() as $record) {
                $model = $record->model();

                // Demo packages/projects are already gone here, so any remaining pivot link is real content.
                if ($model instanceof Service && ($model->packages()->exists() || $model->projects()->exists())) {
                    $kept[] = "{$record->demo_key} (linked to real packages or projects; unlink or unpublish it instead)";

                    continue;
                }

                try {
                    DB::transaction(function () use ($model, $record) {
                        $model?->delete();
                        $record->delete();
                    });
                    $removed++;
                } catch (QueryException) {
                    $kept[] = "{$record->demo_key} (referenced by other records; unpublish it instead)";
                }
            }
        }

        foreach (DemoRecord::whereIn('record_type', ['site_settings', 'company_profile'])->get() as $record) {
            $singleton = $record->model();
            if ($singleton) {
                $restore = [];
                foreach ($record->demo_values ?? [] as $field => $demoValue) {
                    if ($singleton->{$field} == $demoValue) {
                        $restore[$field] = $record->original_values[$field] ?? null;
                    } else {
                        $kept[] = "{$record->record_type}.{$field} (edited since seeding; left as is)";
                    }
                }
                $singleton->fill($restore)->save();
            }
            $record->delete();
            $removed++;
        }

        foreach (DemoRecord::where('record_type', 'media')->get() as $record) {
            $media = Media::find($record->record_id);
            if (! $media || $images->deleteIfUnreferenced($media)) {
                $record->delete();
                $removed++;
            } else {
                $kept[] = "{$record->demo_key} (image still referenced)";
            }
        }

        PublicContentCache::flush();

        $this->info("Removed {$removed} demo item(s).");
        foreach ($kept as $reason) {
            $this->warn("Kept: {$reason}");
        }

        return self::SUCCESS;
    }
}

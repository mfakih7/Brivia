<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tracks one piece of development-only demo content. */
class DemoRecord extends Model
{
    public const TYPES = [
        'service' => Service::class,
        'package' => Package::class,
        'project' => Project::class,
        'project_category' => ProjectCategory::class,
        'team_member' => TeamMember::class,
        'consultation_type' => ConsultationType::class,
        'media' => Media::class,
        'site_settings' => SiteSetting::class,
        'company_profile' => CompanyProfile::class,
    ];

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['original_values' => 'array', 'demo_values' => 'array'];
    }

    public static function isAllowedEnvironment(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    public static function track(string $key, string $type, int $id, ?array $original = null, ?array $demo = null): self
    {
        $record = new self;
        $record->forceFill([
            'demo_key' => $key,
            'record_type' => $type,
            'record_id' => $id,
            'original_values' => $original,
            'demo_values' => $demo,
        ])->save();

        return $record;
    }

    public function model(): ?Model
    {
        $class = self::TYPES[$this->record_type] ?? null;

        return $class ? $class::find($this->record_id) : null;
    }
}

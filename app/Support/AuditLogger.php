<?php

namespace App\Support;

use App\Models\AuditLog;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use InvalidArgumentException;

/**
 * Privacy-safe audit trail. Only allowlisted resource types are accepted. Values are
 * recorded only for operational fields; every other changed field is recorded by name.
 */
class AuditLogger
{
    public const RESOURCE_TYPES = [
        'user', 'invitation', 'service', 'package', 'project', 'project_category', 'team_member', 'company_profile',
        'homepage_content', 'site_settings', 'legal_page', 'consultation_type', 'media', 'enquiry', 'appointment',
        'notification_delivery',
    ];

    /** Fields whose values are safe and useful to keep. */
    private const VALUE_FIELDS = [
        'status', 'price_mode', 'price_amount', 'currency', 'billing_label', 'permission_confirmed', 'role', 'is_active',
        'published_at', 'assigned_to', 'confirmed_start_at_utc', 'confirmed_end_at_utc', 'is_featured', 'slug',
        'pricing_mode', 'amount', 'duration_minutes', 'is_placeholder', 'version_label', 'kind',
    ];

    /** Fields never recorded, not even by name. */
    private const SECRET_FIELDS = [
        'password', 'remember_token', 'token', 'token_hash', 'updated_at', 'created_at', 'lock_version',
    ];

    public function record(string $action, string $resourceType, ?int $resourceId = null, array $changes = [], ?int $actorId = null): AuditLog
    {
        if (! in_array($resourceType, self::RESOURCE_TYPES, true)) {
            throw new InvalidArgumentException("Unknown audit resource type [{$resourceType}].");
        }

        $log = new AuditLog;
        $log->forceFill([
            'actor_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'changed_fields' => $this->redact($changes) ?: null,
            'request_id' => Context::get('request_id'),
        ])->save();

        return $log;
    }

    /** Record the dirty/changed attributes of a model after save. */
    public function recordModelChanges(string $action, string $resourceType, Model $model): ?AuditLog
    {
        $changes = collect($model->getChanges())->except(self::SECRET_FIELDS)->all();

        if ($changes === [] && $action === 'updated') {
            return null;
        }

        return $this->record($action, $resourceType, $model->getKey(), $changes);
    }

    private function redact(array $changes): array
    {
        $result = [];

        foreach ($changes as $field => $value) {
            if (in_array($field, self::SECRET_FIELDS, true)) {
                continue;
            }

            $result[$field] = in_array($field, self::VALUE_FIELDS, true) ? $this->scalar($value) : '[changed]';
        }

        return $result;
    }

    private function scalar(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof DateTimeInterface => $value->format(DATE_ATOM),
            is_bool($value), is_int($value), is_float($value), $value === null => $value,
            default => mb_substr((string) $value, 0, 120),
        };
    }
}

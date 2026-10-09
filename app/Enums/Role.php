<?php

namespace App\Enums;

enum Role: string
{
    case Owner = 'owner';
    case ContentEditor = 'content_editor';
    case OperationsManager = 'operations_manager';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::ContentEditor => 'Content editor',
            self::OperationsManager => 'Operations manager',
        };
    }

    public function canManageContent(): bool
    {
        return in_array($this, [self::Owner, self::ContentEditor], true);
    }

    public function canManageOperations(): bool
    {
        return in_array($this, [self::Owner, self::OperationsManager], true);
    }

    public function canManageStaff(): bool
    {
        return $this === self::Owner;
    }
}

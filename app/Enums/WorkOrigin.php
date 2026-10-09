<?php

namespace App\Enums;

enum WorkOrigin: string
{
    case Brivia = 'brivia';
    case FounderExperience = 'founder_experience';
    case Concept = 'concept';

    public function label(): string
    {
        return match ($this) {
            self::Brivia => 'BRIVIA project',
            self::FounderExperience => 'Earlier founder experience',
            self::Concept => 'Sample concept',
        };
    }

    /** Public qualifier shown on cards; BRIVIA work needs none. */
    public function publicBadge(): ?string
    {
        return $this === self::Brivia ? null : $this->label();
    }
}

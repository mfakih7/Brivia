<?php

namespace App\Enums;

/** Allowlisted decorative icons for services and packages. */
enum IconKey: string
{
    case Lightbulb = 'lightbulb';
    case Layers = 'layers';
    case Chat = 'chat';
    case Gear = 'gear';
    case Monitor = 'monitor';
    case Cube = 'cube';
    case Users = 'users';
    case Compass = 'compass';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

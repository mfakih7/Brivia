<?php

namespace App\Enums;

enum EnquiryType: string
{
    case NewProduct = 'new_product';
    case ImproveExisting = 'improve_existing';
    case Consulting = 'consulting';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::NewProduct => 'A new product',
            self::ImproveExisting => 'Improving an existing product',
            self::Consulting => 'Technical consulting',
            self::General => 'General question',
        };
    }
}

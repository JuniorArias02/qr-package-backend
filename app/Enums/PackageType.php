<?php

namespace App\Enums;

enum PackageType: string
{
    case SINGLE = 'unico';
    case DOUBLE = 'doble';

    public function label(): string
    {
        return match($this) {
            self::SINGLE => 'Único',
            self::DOUBLE => 'Doble',
        };
    }
}

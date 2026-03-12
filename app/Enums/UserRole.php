<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case COORDINATOR = 'coordinator';
    case DRIVER = 'driver';
    case REPORTER = 'reporter';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrador',
            self::COORDINATOR => 'Coordinador',
            self::DRIVER => 'Transportador',
            self::REPORTER => 'Reportero',
        };
    }

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

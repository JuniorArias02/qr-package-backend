<?php

namespace App\Enums;

enum UserRole: string
{
    // Roles multi-tenant (nuevos)
    case SUPER_ADMIN   = 'super_admin';
    case COMPANY_ADMIN = 'company_admin';

    // Roles existentes (sin cambios)
    case COORDINATOR = 'coordinator';
    case DRIVER      = 'driver';
    

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN   => 'Super Administrador',
            self::COMPANY_ADMIN => 'Admin de Empresa',
            self::ADMIN         => 'Administrador',
            self::COORDINATOR   => 'Coordinador',
            self::DRIVER        => 'Transportador',
            self::REPORTER      => 'Reportero',
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

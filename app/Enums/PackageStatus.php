<?php

namespace App\Enums;

enum PackageStatus: string
{
    case PENDING = 'en_espera';
    case DELIVERED = 'entregado';
    case NOT_DELIVERED = 'no_entregado';
    // Backwards compatibility if needed, or migration target
    case IN_TRANSIT = 'en_transito'; 
    case RECEIVED = 'recibido';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En Espera',
            self::DELIVERED => 'Entregado',
            self::NOT_DELIVERED => 'No Entregado',
            self::IN_TRANSIT => 'En Tránsito',
            self::RECEIVED => 'Recibido',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::DELIVERED => 'green',
            self::NOT_DELIVERED => 'red',
            self::IN_TRANSIT => 'blue',
            self::RECEIVED => 'gray',
        };
    }
}

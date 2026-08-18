<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case PENDING_ASSIGNMENT = 'pending_assignment';
    case ASSIGNED = 'assigned';
    case IN_ROUTE = 'in_route';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';

    public function label(): string
    {
        return match($this) {
            self::PENDING_ASSIGNMENT => 'Pendiente de Asignación',
            self::ASSIGNED => 'Asignado',
            self::IN_ROUTE => 'En Ruta',
            self::DELIVERED => 'Entregado',
            self::FAILED => 'Fallido',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING_ASSIGNMENT => 'orange',
            self::ASSIGNED => 'blue',
            self::IN_ROUTE => 'indigo',
            self::DELIVERED => 'green',
            self::FAILED => 'red',
        };
    }
}

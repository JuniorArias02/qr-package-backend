<?php

namespace App\Enums;

enum UserPermission: string
{
    // Paquetes
    case SCAN_PACKAGES = 'scan_packages';
    case CREATE_PACKAGES = 'create_packages';
    case VIEW_PACKAGES = 'view_packages';
    case VIEW_ALL_PACKAGES = 'view_all_packages';
    
    // Asignación
    case ASSIGN_PACKAGES = 'assign_packages';
    case DEFINE_PACKAGE_TYPE = 'define_package_type';
    case ASSIGN_ROUTES = 'assign_routes';
    
    // Entregas
    case UPDATE_DELIVERY_STATUS = 'update_delivery_status';
    case VIEW_DELIVERIES = 'view_deliveries';
    
    // Reportes
    case VIEW_REPORTS = 'view_reports';
    case VIEW_ALL_REPORTS = 'view_all_reports';
    
    // Usuarios
    case MANAGE_USERS = 'manage_users';
    case VIEW_COORDINATORS = 'view_coordinators';

    public function description(): string
    {
        return match($this) {
            self::SCAN_PACKAGES => 'Escanear paquetes',
            self::CREATE_PACKAGES => 'Crear paquetes',
            self::VIEW_PACKAGES => 'Ver paquetes propios',
            self::VIEW_ALL_PACKAGES => 'Ver todos los paquetes',
            self::ASSIGN_PACKAGES => 'Asignar paquetes',
            self::DEFINE_PACKAGE_TYPE => 'Definir tipo de paquete',
            self::ASSIGN_ROUTES => 'Asignar rutas',
            self::UPDATE_DELIVERY_STATUS => 'Actualizar estado de entrega',
            self::VIEW_DELIVERIES => 'Ver entregas',
            self::VIEW_REPORTS => 'Ver reportes',
            self::VIEW_ALL_REPORTS => 'Ver todos los reportes',
            self::MANAGE_USERS => 'Gestionar usuarios',
            self::VIEW_COORDINATORS => 'Ver coordinadores',
        };
    }
}

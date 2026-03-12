<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Package;
use App\Enums\UserPermission;
use App\Enums\UserRole;

class PackagePolicy
{
    /**
     * Determine if user can view any packages
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(UserPermission::VIEW_PACKAGES->value);
    }

    /**
     * Determine if user can view a package
     */
    public function view(User $user, Package $package): bool
    {
        // Admin/Coordinator can view all (via permission check)
        if ($user->hasPermission(UserPermission::VIEW_ALL_PACKAGES->value)) {
            return true;
        }
        
        // Others can only view their own
        return $package->user_id_asignado === $user->id;
    }

    /**
     * Determine if user can create packages
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(UserPermission::CREATE_PACKAGES->value);
    }

    /**
     * Determine if user can assign packages
     */
    public function assign(User $user): bool
    {
        return $user->hasPermission(UserPermission::ASSIGN_PACKAGES->value);
    }

    /**
     * Determine if user can update delivery status
     */
    public function updateDeliveryStatus(User $user, Package $package): bool
    {
        // Must have permission
        if (!$user->hasPermission(UserPermission::UPDATE_DELIVERY_STATUS->value)) {
            return false;
        }
        
        // Admin/Coordinator (implied by role check for broad access, or improved permission logic)
        // Ideally we should have a 'manage_all_deliveries' permission, but for now we check Roles loosely
        // OR better: check if user is admin/coordinator via hasRole helper
        if ($user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::COORDINATOR->value)) {
            return true;
        }
        
        // Driver can only update their own
        return $package->user_id_asignado === $user->id;
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Route;
use App\Enums\UserPermission;
use App\Enums\UserRole;

class RoutePolicy
{
    /**
     * Determine if user can view any routes
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(UserPermission::VIEW_PACKAGES->value);
    }

    /**
     * Determine if user can view a route
     */
    public function view(User $user, Route $route): bool
    {
        // Admin/Coordinator can view all
        if ($user->hasRole(UserRole::ADMIN->value) || $user->hasRole(UserRole::COORDINATOR->value)) {
            return true;
        }

        // Driver can only view their own routes
        return $route->driver_id === $user->id;
    }

    /**
     * Determine if user can create routes (assign routes)
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(UserPermission::ASSIGN_ROUTES->value);
    }

    /**
     * Determine if user can update a route
     */
    public function update(User $user, Route $route): bool
    {
        // Only admin/coord can update routes structure
        return $user->hasPermission(UserPermission::ASSIGN_ROUTES->value);
    }
}

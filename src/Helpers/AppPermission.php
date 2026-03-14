<?php

namespace Daniardev\LaravelTsd\Helpers;

use Illuminate\Support\Facades\Auth;

class AppPermission
{
    /**
     * Check if authenticated user has permission for a feature.
     *
     * @param string|null $feature Feature key to check
     * @return bool True if user has permission, false otherwise
     */
    public static function isAllow(?string $feature): bool
    {
        // Return false if no feature specified or user not authenticated
        if (!$feature || !Auth::check()) {
            return false;
        }

        $user = Auth::user();

        // Return false if user has no role
        if (!$user->role) {
            return false;
        }

        // Return false if role has no permissions
        if (!$user->role->permissions) {
            return false;
        }

        // Check if permission exists for the feature
        $permission = $user->role->permissions->load('feature')
            ->firstWhere('feature.feature', $feature);

        return $permission !== null;
    }
}

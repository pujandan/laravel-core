<?php

namespace DaniarDev\LaravelCore\Helpers;

use Illuminate\Support\Facades\Auth;

class AppPermission
{
    public static function isAllow(?string $feature) : bool
    {
        $feature = Auth::user()->role->permissions->load('feature')->firstWhere('feature.feature', $feature);
        return $feature != null;
    }
}

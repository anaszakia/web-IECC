<?php

if (!function_exists('authUser')) {
    function authUser()
    {
        static $user = null;
        if (!$user && session('user_id')) {
            $user = \App\Models\User::with('roles.permissions', 'passkeys')
                ->find(session('user_id'));
        }
        return $user;
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        return authUser()?->hasPermission($permission) ?? false;
    }
}
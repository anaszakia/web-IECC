<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = User::with('roles')->find(session('user_id'));

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Cek apakah salah satu role user cocok dengan yang diizinkan
        $hasRole = $user->roles->contains(fn($role) => in_array($role->slug, $roles));

        if (!$hasRole) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = User::with('roles.permissions')->find(session('user_id'));

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $hasPermission = $user->roles
            ->flatMap(fn($role) => $role->permissions)
            ->contains(fn($perm) => in_array($perm->slug, $permissions));

        if (!$hasPermission) {
            abort(403, 'Anda tidak memiliki akses untuk tindakan ini.');
        }

        return $next($request);
    }
}
<?php

namespace App\Services;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PermissionSyncService
{
    /**
     * Scan seluruh route untuk mengambil middleware 'permission:{slug}'.
     * Mendaftarkan slug yang belum ada di database dan menghubungkannya ke role superadmin.
     */
    public function syncFromRoutes(): array
    {
        $routes = Route::getRoutes();
        $detectedPermissions = [];

        foreach ($routes as $route) {
            $middlewares = $route->gatherMiddleware();

            // Jika route diarahkan ke Controller, ambil juga middleware dari controller instance
            $controller = $route->getController();
            if ($controller && method_exists($controller, 'getMiddleware')) {
                $actionMethod = $route->getActionMethod();
                foreach ($controller->getMiddleware() as $middlewareInfo) {
                    $mName = $middlewareInfo['middleware'] ?? '';
                    $only = $middlewareInfo['options']['only'] ?? [];
                    $except = $middlewareInfo['options']['except'] ?? [];

                    $applies = true;
                    if (!empty($only) && !in_array($actionMethod, $only)) {
                        $applies = false;
                    }
                    if (!empty($except) && in_array($actionMethod, $except)) {
                        $applies = false;
                    }

                    if ($applies && is_string($mName)) {
                        $middlewares[] = $mName;
                    }
                }
            }

            foreach ($middlewares as $middleware) {
                if (is_string($middleware) && str_starts_with($middleware, 'permission:')) {
                    $permSlug = substr($middleware, strlen('permission:'));
                    // Jika ada multiple permission dipisah koma (contoh: permission:view,edit)
                    $perms = explode(',', $permSlug);
                    foreach ($perms as $slug) {
                        $slug = trim($slug);
                        if ($slug !== '') {
                            $detectedPermissions[$slug] = $this->formatPermissionName($slug);
                        }
                    }
                }
            }
        }

        $superAdminRole = Role::whereIn('slug', ['superadmin', 'admin', 'super-admin'])->first();
        $createdCount = 0;
        $createdSlugs = [];

        foreach ($detectedPermissions as $slug => $name) {
            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );

            if ($permission->wasRecentlyCreated) {
                $createdCount++;
                $createdSlugs[] = $slug;
            }

            if ($superAdminRole && !$superAdminRole->permissions()->where('permissions.id', $permission->id)->exists()) {
                $superAdminRole->permissions()->attach($permission->id);
            }
        }

        return [
            'total_detected' => count($detectedPermissions),
            'created_count'  => $createdCount,
            'created_slugs'  => $createdSlugs,
        ];
    }

    /**
     * Generate CRUD permissions otomatis untuk sebuah resource (misal saat tambah menu).
     */
    public function generateCrudPermissions(string $resourceName, string $resourceSlug, array $roleIds = []): array
    {
        $actions = [
            'view'   => 'Lihat ' . $resourceName,
            'create' => 'Tambah ' . $resourceName,
            'edit'   => 'Edit ' . $resourceName,
            'delete' => 'Hapus ' . $resourceName,
        ];

        $createdPermissions = [];
        $superAdminRole = Role::whereIn('slug', ['superadmin', 'admin', 'super-admin'])->first();

        foreach ($actions as $action => $name) {
            $slug = Str::slug($resourceSlug) . '.' . $action;

            $permission = Permission::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );

            // Sync role yang dipilih
            if (!empty($roleIds)) {
                $permission->roles()->syncWithoutDetaching($roleIds);
            }

            // Selalu assign ke superadmin
            if ($superAdminRole && !$superAdminRole->permissions()->where('permissions.id', $permission->id)->exists()) {
                $superAdminRole->permissions()->attach($permission->id);
            }

            $createdPermissions[] = $permission;
        }

        return $createdPermissions;
    }

    /**
     * Format nama manusiawi dari slug permission.
     * Contoh: "users.create" -> "Tambah Users", "reports.view" -> "Lihat Reports"
     */
    private function formatPermissionName(string $slug): string
    {
        $parts = explode('.', $slug);
        if (count($parts) === 2) {
            [$resource, $action] = $parts;
            $actionLabel = match ($action) {
                'view', 'index', 'show' => 'Lihat',
                'create', 'store'       => 'Tambah',
                'edit', 'update'        => 'Edit',
                'delete', 'destroy'     => 'Hapus',
                default                 => Str::headline($action),
            };
            return $actionLabel . ' ' . Str::headline($resource);
        }

        return Str::headline(str_replace(['.', '-', '_'], ' ', $slug));
    }
}

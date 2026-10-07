<?php

namespace App\Traits;

use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

trait HasAutoPermissions
{
    /**
     * Daftarkan middleware permission secara otomatis menggunakan standar Laravel 11 (HasMiddleware).
     *
     * Contoh:
     * ProductController -> slug: 'products'
     * index/show -> permission:products.view
     * create/store -> permission:products.create
     * edit/update -> permission:products.edit
     * destroy -> permission:products.delete
     *
     * @return array<Middleware>
     */
    public static function middleware(): array
    {
        $slug = static::resolveResourceSlugStatic();

        return [
            new Middleware("permission:{$slug}.view", only: ['index', 'show']),
            new Middleware("permission:{$slug}.create", only: ['create', 'store']),
            new Middleware("permission:{$slug}.edit", only: ['edit', 'update']),
            new Middleware("permission:{$slug}.delete", only: ['destroy']),
        ];
    }

    /**
     * Dapatkan nama resource slug dari nama Class Controller secara static tanpa instantiate constructor.
     * Contoh: UserController -> users, ProductItemController -> product-items
     */
    protected static function resolveResourceSlugStatic(): string
    {
        if (property_exists(static::class, 'resourcePermissionSlug')) {
            $vars = get_class_vars(static::class);
            if (!empty($vars['resourcePermissionSlug'])) {
                return $vars['resourcePermissionSlug'];
            }
        }

        $className = class_basename(static::class);
        $baseName = preg_replace('/Controller$/', '', $className);

        return Str::plural(Str::kebab($baseName));
    }
}

<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    /**
     * Get paginated permissions with search and roles
     */
    public static function getPaginatedPermissions(?string $search = null, int $perPage = 10)
    {
        $query = static::with('roles');

        if (!empty($search)) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('slug')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get all permissions grouped by module prefix (e.g. 'users', 'roles', 'units', 'command-center')
     */
    public static function getGroupedByModule()
    {
        $permissions = static::orderBy('slug')->get();

        return $permissions->groupBy(function ($perm) {
            $parts = explode('.', $perm->slug);
            return count($parts) > 1 ? ucfirst($parts[0]) : 'Umum';
        });
    }
}

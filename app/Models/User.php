<?php

namespace App\Models;

use App\Models\Auth\Role;
use App\Models\Master\Agency;
use App\Traits\HasUlid;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

class User extends Authenticatable implements PasskeyUser
{
    use PasskeyAuthenticatable, HasUlid;

    /**
     * Pastikan parameter route key selalu menghasilkan nilai (ulid atau fallback ke id)
     */
    public function getRouteKey()
    {
        return $this->ulid ?: $this->getKey();
    }

    protected $fillable = [
        'ulid',
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'address',
        'role_id',
        'agency_id',
        'user_type',
        'is_active',
        'google_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = ['password', 'remember_token'];

    // Instansi (Agency)
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    // Primary role (one-to-many via role_id column)
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Multiple roles (many-to-many via user_role pivot table)
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    // Keanggotaan Armada Unit
    public function unitMember()
    {
        return $this->hasOne(\App\Models\Master\UnitMember::class);
    }

    public function unit()
    {
        return $this->hasOneThrough(\App\Models\Master\Unit::class, \App\Models\Master\UnitMember::class, 'user_id', 'id', 'id', 'unit_id');
    }

    /**
     * Cek role user menggunakan direct raw query/query builder (ultra fast untuk jutaan data)
     */
    public function hasRole(string $slug): bool
    {
        return \Illuminate\Support\Facades\DB::table('user_role')
            ->join('roles', 'roles.id', '=', 'user_role.role_id')
            ->where('user_role.user_id', $this->id)
            ->where('roles.slug', $slug)
            ->exists();
    }

    /**
     * Cek permission user menggunakan direct raw JOIN query (ultra fast untuk jutaan data)
     */
    public function hasPermission(string $slug): bool
    {
        return \Illuminate\Support\Facades\DB::table('user_role')
            ->join('role_permission', 'role_permission.role_id', '=', 'user_role.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('user_role.user_id', $this->id)
            ->where('permissions.slug', $slug)
            ->exists();
    }

    /**
     * Cek multiple permission user menggunakan direct raw JOIN query
     */
    public function hasAnyPermission(array $slugs): bool
    {
        return \Illuminate\Support\Facades\DB::table('user_role')
            ->join('role_permission', 'role_permission.role_id', '=', 'user_role.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->where('user_role.user_id', $this->id)
            ->whereIn('permissions.slug', $slugs)
            ->exists();
    }

    /**
     * Find user by ID with high-performance optimized query
     */
    public static function findOptimized(int|string $id): ?self
    {
        return static::with('role', 'roles')->find($id);
    }

    /**
     * Find user by Google ID or Email via direct query
     */
    public static function findByGoogleOrEmail(?string $googleId, ?string $email): ?self
    {
        return static::where(function ($query) use ($googleId, $email) {
            if ($googleId) {
                $query->where('google_id', $googleId);
            }
            if ($email) {
                $query->orWhere('email', $email);
            }
        })->first();
    }

    /**
     * Get paginated users with indexed search & lean relation loading
     */
    public static function getPaginatedUsers(?string $search = null, int $perPage = 10)
    {
        $query = static::select('users.id', 'users.ulid', 'users.name', 'users.email', 'users.phone', 'users.avatar', 'users.role_id', 'users.created_at')
            ->with(['role:id,name,slug', 'roles:id,name,slug']);

        if (!empty($search)) {
            $query->where(function ($sub) use ($search) {
                $sub->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('users.phone', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('users.name')
            ->paginate($perPage)
            ->withQueryString();
    }

    // Helper: ambil nama primary role
    public function getRoleNameAttribute(): string
    {
        return $this->role?->name ?? $this->roles->first()?->name ?? 'No Role';
    }

    public function getAvatarUrlAttribute(): string
    {
        return minio_avatar($this->avatar, $this->name);
    }
}

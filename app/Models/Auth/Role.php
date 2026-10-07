<?php

namespace App\Models\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role');
    }

    public function primaryUsers()
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_role');
    }

    /**
     * Get paginated roles with search and user count
     */
    public static function getPaginatedRoles(?string $search = null, int $perPage = 10)
    {
        $query = static::withCount('users');

        if (!empty($search)) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get all ordered roles
     */
    public static function getAllOrdered()
    {
        return static::orderBy('name')->get();
    }
}

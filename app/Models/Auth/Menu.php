<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'url',
        'parent_id',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'menu_role');
    }

    /**
     * Get paginated root menus with relations and counts
     */
    public static function getPaginatedMenus(?string $search = null, int $perPage = 10)
    {
        $query = static::with(['parent', 'roles', 'children.roles'])
            ->withCount('children')
            ->whereNull('parent_id');

        if (!empty($search)) {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhere('icon', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('order')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get root menus with ordered children for role form selection
     */
    public static function getRootMenusWithChildren()
    {
        return static::with('children')->whereNull('parent_id')->orderBy('order')->get();
    }

    /**
     * Get parent options for menu form
     */
    public static function getParentOptions(?int $excludeId = null)
    {
        $query = static::whereNull('parent_id')->orderBy('order');
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->get();
    }
}

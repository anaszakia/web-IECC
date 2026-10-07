<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auth\Menu;
use App\Models\Auth\Role;
use App\Services\PermissionSyncService;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $menus = Menu::getPaginatedMenus($request->query('search'), 10);

        return view('admin.menus.index', compact('menus'));
    }

    public function create()
    {
        $parents = Menu::getParentOptions();
        $roles   = Role::getAllOrdered();
        return view('admin.menus.create', compact('parents', 'roles'));
    }

    public function store(Request $request, PermissionSyncService $permissionSyncService)
    {
        $request->validate([
            'name'                    => 'required|string|max:100',
            'url'                     => 'nullable|string|max:255',
            'icon'                    => 'nullable|string|max:100',
            'parent_id'               => 'nullable|exists:menus,id',
            'order'                   => 'nullable|integer',
            'roles'                   => 'nullable|array',
            'roles.*'                 => 'exists:roles,id',
            'auto_generate_permissions' => 'nullable|boolean',
            'permission_slug'         => 'nullable|string|max:100',
        ]);

        $menu = Menu::create([
            'name'      => $request->name,
            'url'       => $request->url,
            'icon'      => $request->icon,
            'parent_id' => $request->parent_id,
            'order'     => $request->order ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        if ($request->roles) {
            $menu->roles()->sync($request->roles);
        }

        $flashMessage = 'Menu berhasil ditambahkan!';

        // Auto generate CRUD permissions jika opsi dicentang
        if ($request->boolean('auto_generate_permissions')) {
            $baseSlug = $request->filled('permission_slug')
                ? $request->permission_slug
                : trim(str_replace('/', '', (string) $request->url));

            if (!$baseSlug) {
                $baseSlug = \Illuminate\Support\Str::slug($request->name);
            }

            $permissionSyncService->generateCrudPermissions(
                $request->name,
                $baseSlug,
                $request->roles ?? []
            );

            $flashMessage .= " & 4 Permission CRUD ({$baseSlug}.*) berhasil dibuat otomatis!";
        }

        return redirect()->route('menus.index')
            ->with('success', $flashMessage);
    }

    public function edit(Menu $menu)
    {
        $parents = Menu::getParentOptions($menu->id);
        $roles   = Role::getAllOrdered();
        $menu->load('roles');

        return view('admin.menus.edit', compact('menu', 'parents', 'roles'));
    }

    public function update(Request $request, Menu $menu)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'url'       => 'nullable|string|max:255',
            'icon'      => 'nullable|string|max:100',
            'parent_id' => 'nullable|exists:menus,id',
            'order'     => 'nullable|integer',
            'roles'     => 'nullable|array',
            'roles.*'   => 'exists:roles,id',
        ]);

        $menu->update([
            'name'      => $request->name,
            'url'       => $request->url,
            'icon'      => $request->icon,
            'parent_id' => $request->parent_id,
            'order'     => $request->order ?? 0,
            'is_active' => $request->has('is_active'),
        ]);

        $menu->roles()->sync($request->roles ?? []);

        return redirect()->route('menus.index')
            ->with('success', 'Menu berhasil diupdate!');
    }

    public function destroy(Menu $menu)
    {
        // Hapus children dulu
        $menu->children()->delete();
        $menu->roles()->detach();
        $menu->delete();

        return redirect()->route('menus.index')
            ->with('success', 'Menu berhasil dihapus!');
    }
}

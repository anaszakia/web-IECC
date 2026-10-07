<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auth\Menu;
use App\Models\Auth\Role;
use App\Models\Auth\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::getPaginatedRoles($request->query('search'), 10);

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $menus = Menu::getRootMenusWithChildren();
        $permissions = Permission::getGroupedByModule();

        return view('admin.roles.create', compact('menus', 'permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:100|unique:roles,name',
            'slug'          => 'required|string|max:100|unique:roles,slug',
            'menus'         => 'nullable|array',
            'menus.*'       => 'exists:menus,id',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'slug' => $request->slug,
        ]);

        $role->menus()->sync($request->menus ?? []);
        $role->permissions()->sync($request->permissions ?? []);

        return redirect()->route('roles.index')
            ->with('success', 'Role berhasil ditambahkan!');
    }

    public function edit(Role $role)
    {
        $menus = Menu::getRootMenusWithChildren();
        $permissions = Permission::getGroupedByModule();

        $role->load('menus', 'permissions');

        return view('admin.roles.edit', compact('role', 'menus', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name'          => 'required|string|max:100|unique:roles,name,' . $role->id,
            'slug'          => 'required|string|max:100|unique:roles,slug,' . $role->id,
            'menus'         => 'nullable|array',
            'menus.*'       => 'exists:menus,id',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => $request->name,
            'slug' => $request->slug,
        ]);

        $role->menus()->sync($request->menus ?? []);
        $role->permissions()->sync($request->permissions ?? []);

        return redirect()->route('roles.index')
            ->with('success', 'Role berhasil diupdate!');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')
                ->with('error', 'Role tidak bisa dihapus karena masih digunakan oleh ' . $role->users()->count() . ' user!');
        }

        $role->menus()->detach();
        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role berhasil dihapus!');
    }
}

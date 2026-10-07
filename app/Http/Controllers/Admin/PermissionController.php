<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use App\Services\PermissionSyncService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function syncRoutes(PermissionSyncService $syncService)
    {
        $result = $syncService->syncFromRoutes();

        $message = "Sinkronisasi berhasil! {$result['total_detected']} permission terdeteksi di route, {$result['created_count']} permission baru ditambahkan.";
        
        return redirect()->route('permissions.index')->with('success', $message);
    }

    public function index(Request $request)
    {
        $permissions = Permission::getPaginatedPermissions($request->query('search'), 10);

        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        $roles = Role::getAllOrdered();
        return view('admin.permissions.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:100',
            'slug'    => 'required|string|max:100|unique:permissions,slug',
            'roles'   => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'slug' => $request->slug,
        ]);

        if ($request->roles) {
            $permission->roles()->sync($request->roles);
        }

        return redirect()->route('permissions.index')
            ->with('success', 'Permission berhasil ditambahkan!');
    }

    public function edit(Permission $permission)
    {
        $roles = Role::getAllOrdered();
        $permission->load('roles');
        return view('admin.permissions.edit', compact('permission', 'roles'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name'    => 'required|string|max:100',
            'slug'    => 'required|string|max:100|unique:permissions,slug,' . $permission->id,
            'roles'   => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $permission->update([
            'name' => $request->name,
            'slug' => $request->slug,
        ]);

        $permission->roles()->sync($request->roles ?? []);

        return redirect()->route('permissions.index')
            ->with('success', 'Permission berhasil diupdate!');
    }

    public function destroy(Permission $permission)
    {
        $permission->roles()->detach();
        $permission->delete();

        return redirect()->route('permissions.index')
            ->with('success', 'Permission berhasil dihapus!');
    }
}

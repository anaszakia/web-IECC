<?php

namespace Database\Seeders;

use App\Models\Auth\Menu;
use App\Models\Auth\Role;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('slug', 'superadmin')->first();

        $dashboard = $this->menu([
            'name'  => 'Dashboard',
            'url'   => '/dashboard',
            'icon'  => 'ti ti-layout-dashboard',
            'order' => 1,
        ]);

        $commandCenter = $this->menu([
            'name'  => 'Command Center',
            'url'   => '/command-center',
            'icon'  => 'ti ti-broadcast',
            'order' => 2,
        ]);

        $incidentHistory = $this->menu([
            'name'  => 'Riwayat Kejadian',
            'url'   => '/incidents/history',
            'icon'  => 'ti ti-history',
            'order' => 3,
        ]);

        $hospitalPortal = $this->menu([
            'name'  => 'Hospital Portal',
            'url'   => '/hospital-portal',
            'icon'  => 'ti ti-building-hospital',
            'order' => 4,
        ]);

        $executiveDashboard = $this->menu([
            'name'  => 'Executive City Dashboard',
            'url'   => '/executive-dashboard',
            'icon'  => 'ti ti-chart-arrows-vertical',
            'order' => 5,
        ]);

        $menuManagement = $this->menu([
            'name'  => 'Menu Management',
            'url'   => null,
            'icon'  => 'ti ti-menu-deep',
            'order' => 6,
        ]);

        $children = [
            [
                'name'      => 'User',
                'url'       => '/users',
                'icon'      => 'ti ti-users',
                'parent_id' => $menuManagement->id,
                'order'     => 1,
            ],
            [
                'name'      => 'Roles',
                'url'       => '/roles',
                'icon'      => 'ti ti-shield',
                'parent_id' => $menuManagement->id,
                'order'     => 2,
            ],
            [
                'name'      => 'Permission',
                'url'       => '/permissions',
                'icon'      => 'ti ti-key',
                'parent_id' => $menuManagement->id,
                'order'     => 3,
            ],
            [
                'name'      => 'Menus',
                'url'       => '/menus',
                'icon'      => 'ti ti-menu-2',
                'parent_id' => $menuManagement->id,
                'order'     => 4,
            ],
            [
                'name'      => 'Master Unit',
                'url'       => '/units',
                'icon'      => 'ti ti-truck',
                'parent_id' => $menuManagement->id,
                'order'     => 5,
            ],
            [
                'name'      => 'Master Fasilitas',
                'url'       => '/facilities',
                'icon'      => 'ti ti-building-hospital',
                'parent_id' => $menuManagement->id,
                'order'     => 6,
            ],
        ];

        $menus = collect([$dashboard, $commandCenter, $incidentHistory, $hospitalPortal, $executiveDashboard, $menuManagement]);

        foreach ($children as $child) {
            $menus->push($this->menu($child));
        }

        $roles = Role::all();
        $superadmin = Role::where('slug', 'superadmin')->first();
        $operator = Role::where('slug', 'operator')->first();
        $hospitalStaff = Role::where('slug', 'hospital-staff')->first();
        $executive = Role::where('slug', 'executive')->first();

        foreach ($menus as $menu) {
            // Superadmin gets everything
            if ($superadmin) {
                $menu->roles()->syncWithoutDetaching([$superadmin->id]);
            }
        }

        // Operator gets Dashboard, Command Center, Riwayat Kejadian, Hospital Portal
        if ($operator) {
            $operator->menus()->syncWithoutDetaching([$dashboard->id, $commandCenter->id, $incidentHistory->id, $hospitalPortal->id]);
        }

        // Hospital staff gets Hospital Portal & Dashboard
        if ($hospitalStaff) {
            $hospitalStaff->menus()->syncWithoutDetaching([$dashboard->id, $hospitalPortal->id]);
        }

        // Executive gets Executive City Dashboard, Riwayat Kejadian & Dashboard
        if ($executive) {
            $executive->menus()->syncWithoutDetaching([$dashboard->id, $executiveDashboard->id, $incidentHistory->id]);
        }
    }

    private function menu(array $data): Menu
    {
        $lookup = $data['url'] === null
            ? ['name' => $data['name'], 'url' => null]
            : ['url' => $data['url']];

        return Menu::updateOrCreate(
            $lookup,
            [
                'name'      => $data['name'],
                'icon'      => $data['icon'],
                'parent_id' => $data['parent_id'] ?? null,
                'order'     => $data['order'],
                'is_active' => true,
            ]
        );
    }
}

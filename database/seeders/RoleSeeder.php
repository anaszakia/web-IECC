<?php
namespace Database\Seeders;

use App\Models\Auth\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::updateOrCreate(
            ['slug' => 'superadmin'],
            ['name' => 'SuperAdmin']
        );

        Role::updateOrCreate(
            ['slug' => 'user'],
            ['name' => 'User']
        );

        $superAdmin = User::where('email', 'superadmin@gmail.com')->first();

        if ($superAdmin) {
            $superAdmin->update([
                'name'     => 'Super Admin',
                'role_id'  => $admin->id,
            ]);
        } else {
            $superAdmin = User::create([
                'name'     => 'Super Admin',
                'email'    => 'superadmin@gmail.com',
                'password' => Hash::make('12345678'),
                'role_id'  => $admin->id,
            ]);
        }

        $superAdmin->roles()->syncWithoutDetaching([$admin->id]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;

class RolePermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = Permission::all()->keyBy('name');

        $rolePermissions = [
            'admin' => [
                'view_dashboard',
                'manage_users',
                'create_funds',
                'close_funds',
                'assign_commissions',
                'process_payments',
                'sign_contracts',
            ],
            'client' => [
                'view_dashboard',
                'process_payments',
                'sign_contracts',
            ],
            'staff' => [
                'view_dashboard',
                'assign_commissions',
            ],
            'support' => [
                'view_dashboard',
                'sign_contracts',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::where('name', $roleName)->first();
            if (!$role) continue;

            foreach ($perms as $permName) {
                $permission = $permissions[$permName] ?? null;
                if ($permission) {
                    RolePermission::firstOrCreate([
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                    ]);
                }
            }
        }
    }
}


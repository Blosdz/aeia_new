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
                'view_admin',
                'manage_users',
                'create_funds',
                'close_funds',
                'assign_commissions',
                'process_payments',
                'sign_contracts',
                'watch_funds',
                'edit_profiles',
                'manage_funds',
                'watch_reward',
                'manage_funds',
                'watch_contracts',
            ],
            'client' => [
                'view_dashboard',
                'sign_contracts',
                'make_payments',
                'watch_fund',
                'edit_profile',
                'watch_reward',
                'watch_my_contracts',
            ],
            'staff' => [
                'view_staff',
                'sign_contracts',
                'edit_profile',
                'watch_reward',
                'watch_my_contracts',
            ],
            'support' => [
                'view_support',
                'sign_contracts',
                'edit_profile',
                'process_payments',
                'sign_contracts',
                'watch_reward',
                'manage_users',
                'watch_my_contracts',
            ],
            'supervisor' => [
                'view_supervisor',
                'sign_contracts',
                'manage_users',
                'assign_commissions',
                'edit_profile',
                'watch_reward',
                'watch_my_contracts',
            ],
            'client_business' => [
                'view_dashboard',
                'sign_contracts',
                'make_payments',
                'watch_fund',
                'edit_profile',
                'watch_reward',
                'watch_my_contracts',
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


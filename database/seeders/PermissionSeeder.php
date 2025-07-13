<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'view_dashboard', 'description' => 'Ver panel de control'],
            ['name' => 'manage_users', 'description' => 'Gestionar usuarios'],
            ['name' => 'create_funds', 'description' => 'Crear fondos'],
            ['name' => 'close_funds', 'description' => 'Cerrar fondos'],
            ['name' => 'assign_commissions', 'description' => 'Asignar comisiones'],
            ['name' => 'process_payments', 'description' => 'Procesar pagos'],
            ['name' => 'sign_contracts', 'description' => 'Firmar contratos'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }
    }
}

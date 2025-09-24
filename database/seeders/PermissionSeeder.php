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
            ['name' => 'view_admin', 'description' => 'Ver panel de control'],
            ['name' => 'view_staff', 'description' => 'Ver panel de control'],
            ['name' => 'view_support', 'description' => 'Ver panel de control'], //support
            ['name' => 'view_supervisor', 'description' => 'Ver panel de control'],
            ['name' => 'manage_users', 'description' => 'Gestionar usuarios'], //admin (todos los usuarios) supervisor (con su id de supervisor los que estan con su codigo support (todos los clientes menos supervisados - apoyo a supervisor)
            ['name' => 'create_funds', 'description' => 'Crear fondos'], //admin
            ['name' => 'close_funds', 'description' => 'Cerrar fondos'], //admin
            ['name' => 'assign_commissions', 'description' => 'Asignar comisiones'], //admin supervisor(sus usuarios)
            ['name' => 'process_payments', 'description' => 'Procesar pagos'], //support
            ['name' => 'sign_contracts', 'description' => 'Firmar contratos'], //support client staff business supervisor 
            ['name' => 'make_payments', 'description' => 'Hacer Pagos'], // client business
            ['name' => 'watch_funds', 'description' => 'Ver Fondos'], //admin 
            ['name' => 'watch_fund', 'description' => 'Ver mis Fondos'], //client business 
            ['name' => 'edit_profiles', 'description' => 'Editar Perfiles'], //admin staff
            ['name' => 'edit_profile', 'description' => 'Editar mi Perfil'], //client business supervisor staff
            ['name' => 'watch_reward', 'description' => 'Ver mis Ganancias'], //client business staff support supervisor admin
            ['name' => 'manage_funds', 'description' => 'Editar Fondos'], // admin
            ['name' => 'watch_contracts', 'description' => 'Ver Contratos'], //admin
            ['name' => 'watch_my_contracts', 'description' => 'Ver mis Contratos'], // client business support staff supervisor

        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }
    }
}

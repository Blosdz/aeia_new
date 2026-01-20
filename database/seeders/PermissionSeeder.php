<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Permisos de visualización de dashboards
            ['name' => 'view_dashboard', 'description' => 'Ver panel de control general - Client, Client Business'],
            ['name' => 'view_admin', 'description' => 'Ver panel administrativo - Admin'],
            ['name' => 'view_staff', 'description' => 'Ver panel de empleado - Staff'],
            ['name' => 'view_support', 'description' => 'Ver panel de soporte - Support'],
            ['name' => 'view_supervisor', 'description' => 'Ver panel de supervisor - Supervisor'],
            
            // Permisos de gestión de usuarios
            ['name' => 'manage_users', 'description' => 'Gestionar usuarios - Admin (todos), Supervisor (su equipo), Support (clientes sin supervisor)'],
            ['name' => 'edit_profiles', 'description' => 'Editar perfiles de otros - Admin, Staff'],
            ['name' => 'edit_profile', 'description' => 'Editar mi perfil - Client, Business, Supervisor, Staff, Support'],
            
            // Permisos de fondos
            ['name' => 'create_funds', 'description' => 'Crear fondos - Admin'],
            ['name' => 'close_funds', 'description' => 'Cerrar fondos - Admin'],
            ['name' => 'manage_funds', 'description' => 'Editar fondos - Admin'],
            ['name' => 'watch_funds', 'description' => 'Ver todos los fondos - Admin'],
            ['name' => 'watch_fund', 'description' => 'Ver mis fondos personales - Client, Business'],
            
            // Permisos de pagos y comisiones
            ['name' => 'make_payments', 'description' => 'Hacer pagos - Client, Client Business'],
            ['name' => 'process_payments', 'description' => 'Procesar pagos - Admin, Support'],
            ['name' => 'assign_commissions', 'description' => 'Asignar comisiones - Admin, Supervisor (a su equipo)'],
            
            // Permisos de ganancias y recompensas
            ['name' => 'watch_reward', 'description' => 'Ver mis ganancias/recompensas - Todos los roles'],
            
            // Permisos de contratos
            ['name' => 'sign_contracts', 'description' => 'Firmar contratos - Admin, Client, Staff, Business, Support, Supervisor'],
            ['name' => 'watch_contracts', 'description' => 'Ver todos los contratos - Admin'],
            ['name' => 'watch_my_contracts', 'description' => 'Ver mis contratos - Client, Staff, Business, Support, Supervisor'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }
    }
}

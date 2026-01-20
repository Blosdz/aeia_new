<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'description' => 'Administrador del sistema - Control total, gestión de fondos y usuarios'],
            ['name' => 'client', 'description' => 'Cliente o inversor estándar - Acceso a plataforma para invertir'],
            ['name' => 'staff', 'description' => 'Empleado del sistema - Acceso interno con comisiones, vinculado a supervisor'],
            ['name' => 'support', 'description' => 'Soporte técnico y verificador - Procesamiento de pagos y KYC/AML'],
            ['name' => 'supervisor', 'description' => 'Supervisor de operaciones y gestor comercial - Gestiona staff y clientes bajo su código'],
            ['name' => 'client_business', 'description' => 'Cliente empresarial - Inversor corporate con acceso similar a client'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}

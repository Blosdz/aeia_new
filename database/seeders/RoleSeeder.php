<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'description' => 'Administrador del sistema'],
            ['name' => 'client', 'description' => 'Cliente o inversor'],
            ['name' => 'staff', 'description' => 'Empleado del sistema (comisiones internas)'], //leads suscriptor
            ['name' => 'support', 'description' => 'Soporte técnico'], // Verificador
            ['name' => 'supervisor', 'description' => 'Supervisor de operaciones'], //gestor comercial 
            ['name' => 'client_business', 'description' => 'Cliente business'], 
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}

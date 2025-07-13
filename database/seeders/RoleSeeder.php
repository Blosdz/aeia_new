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
            ['name' => 'staff', 'description' => 'Empleado del sistema (comisiones internas)'],
            ['name' => 'support', 'description' => 'Soporte técnico'],
            ['name' => 'supervisor', 'description' => 'Supervisor de operaciones'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear rol admin si no existe
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrador del sistema']
        );

        // Crear usuario admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@aeia.com'],
            [
                'name' => 'Administrador AEIA',
                'first_name' => 'Admin',
                'last_name' => 'AEIA',
                'password' => Hash::make('Admin@12345'),
                'unique_code' => 'ADMIN_' . now()->format('Ymd') . '_001',
                'referral_code' => 'ADMIN_REF_001',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Crear perfil para el admin si no existe
        if (!$admin->profile) {
            Profile::create([
                'user_id' => $admin->id,
                'first_name' => 'Admin',
                'last_name' => 'AEIA',
                'type' => 'boss', // El admin es tipo boss
                'phone' => '+1234567890',
                'country' => 'Venezuela',
                'verified' => 1,
                'verification_status' => 'verified',
                'verified_at' => now(),
            ]);
        }

        // Asignar rol admin
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->command->info('✅ Usuario admin creado exitosamente!');
        $this->command->info('👤 Nombre: Administrador AEIA');
        $this->command->info('📧 Email: admin@aeia.com');
        $this->command->info('🔐 Contraseña: Admin@12345');
        $this->command->info('🆔 Código único: ' . $admin->unique_code);
        $this->command->info('📋 Perfil: ' . ($admin->profile ? 'Creado' : 'Ya existía'));
        $this->command->warn('⚠️  Por favor cambia esta contraseña después del primer login');
    }
}

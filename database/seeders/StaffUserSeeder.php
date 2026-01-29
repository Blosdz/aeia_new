<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;
use App\Models\ProfileBoss;
use App\Models\ProfileStaff;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar el admin AEIA para asignar como boss
        $adminUser = User::where('email', 'admin@aeia.com')->first();
        
        if (!$adminUser) {
            $this->command->error('❌ No se encontró el usuario admin@aeia.com. Ejecuta AdminUserSeeder primero.');
            return;
        }

        $adminProfile = $adminUser->profile;
        
        if (!$adminProfile) {
            $this->command->error('❌ El admin no tiene perfil asociado.');
            return;
        }

        // Crear organización AEIA si no existe
        $organization = \App\Models\Organization::firstOrCreate(
            ['name' => 'AEIA'],
            ['uuid' => \Illuminate\Support\Str::uuid()]
        );

        // Buscar o crear ProfileBoss para el admin
        $boss = ProfileBoss::firstOrCreate(
            ['profile_id' => $adminProfile->id],
            ['organization_id' => $organization->id]
        );

        // Crear o reutilizar el usuario de staff por email
        $staffUser = User::firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Carlos Mendoza',
                'first_name' => 'Carlos',
                'last_name' => 'Mendoza',
                'password' => Hash::make('password'),
                'unique_code' => 'STAFF-' . strtoupper(Str::random(8)),
                'referral_code' => strtoupper(Str::random(10)),
                'is_active' => true,
            ]
        );

        // Crear o reutilizar el perfil del staff
        $staffProfile = Profile::firstOrCreate(
            ['user_id' => $staffUser->id],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Mendoza',
                'type' => 'staff',
                'type_document' => 'dni',
                'dni' => 'DNI' . rand(10000000, 99999999),
                'phone_extension' => '+58',
                'phone' => '4141234567',
                'nacionality' => 'VE',
                'city' => 'Caracas',
                'country' => 'Venezuela',
                'job' => 'Asesor Comercial',
                'country_dni' => 'VE',
                'state' => 'Distrito Capital',
                'birthdate' => '1990-05-15',
                'sex' => 'M',
                'verified' => true,
                'verification_status' => 'verified',
            ]
        );

        // Crear o reutilizar ProfileStaff asociado al boss (admin AEIA)
        ProfileStaff::firstOrCreate([
            'profile_id' => $staffProfile->id,
            'boss_id' => $boss->id,
        ]);

        // Asignar rol staff sin duplicar
        $staffRole = Role::where('name', 'staff')->first();
        if ($staffRole) {
            $staffUser->roles()->syncWithoutDetaching([$staffRole->id]);
        }

        $this->command->info('✅ Usuario staff creado exitosamente:');
        $this->command->info("   Email: {$staffUser->email}");
        $this->command->info("   Password: password");
        $this->command->info("   Código de referido: {$staffUser->referral_code}");
        $this->command->info("   Asociado al boss: {$boss->profile->first_name} {$boss->profile->last_name}");
    }
}

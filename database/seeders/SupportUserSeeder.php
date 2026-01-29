<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SupportUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear o reutilizar el usuario de support por email
        $supportUser = User::firstOrCreate(
            ['email' => 'support@example.com'],
            [
                'name' => 'Ana Martinez',
                'first_name' => 'Ana',
                'last_name' => 'Martinez',
                'password' => Hash::make('password'),
                'unique_code' => 'SUPPORT-' . strtoupper(Str::random(8)),
                'referral_code' => strtoupper(Str::random(10)),
                'is_active' => true,
            ]
        );

        // Crear o reutilizar el perfil del support
        $supportProfile = Profile::firstOrCreate(
            ['user_id' => $supportUser->id],
            [
                'first_name' => 'Ana',
                'last_name' => 'Martinez',
                'type' => 'user',
                'type_document' => 'dni',
                'dni' => 'DNI' . rand(10000000, 99999999),
                'phone_extension' => '+58',
                'phone' => '4242345678',
                'nacionality' => 'VE',
                'city' => 'Valencia',
                'country' => 'Venezuela',
                'job' => 'Soporte Técnico',
                'country_dni' => 'VE',
                'state' => 'Carabobo',
                'birthdate' => '1995-03-20',
                'sex' => 'F',
                'verified' => true,
                'verification_status' => 'verified',
            ]
        );

        // Asignar rol support sin duplicar
        $supportRole = Role::where('name', 'support')->first();
        if ($supportRole) {
            $supportUser->roles()->syncWithoutDetaching([$supportRole->id]);
        }

        $this->command->info('✅ Usuario support creado exitosamente:');
        $this->command->info("   Email: {$supportUser->email}");
        $this->command->info("   Password: password");
        $this->command->info("   Código de referido: {$supportUser->referral_code}");
        $this->command->info("   Rol: Support");
    }
}

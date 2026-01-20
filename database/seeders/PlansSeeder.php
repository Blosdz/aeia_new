<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plans; // 👈 Usa el nombre correcto de tu modelo

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            // Planes de inversión
            ['category' => 'investment', 'name' => 'Cobre', 'amount_min' => 300, 'amount_max' => 599, 'img_url' => '', 'periodicity' => 'annual', 'membership' => 25],
            ['category' => 'investment', 'name' => 'Silver', 'amount_min' => 600, 'amount_max' => 899, 'img_url' => '', 'periodicity' => 'annual', 'membership' => 50],
            ['category' => 'investment', 'name' => 'Gold', 'amount_min' => 900, 'amount_max' => 1199, 'img_url' => '', 'periodicity' => 'annual', 'membership' => 75],
            ['category' => 'investment', 'name' => 'Platinum', 'amount_min' => 1200, 'amount_max' => 1499, 'img_url' => '', 'periodicity' => 'annual', 'membership' => 100],
            ['category' => 'investment', 'name' => 'Diamante', 'amount_min' => 1500, 'amount_max' => 1799, 'img_url' => '', 'periodicity' => 'annual', 'membership' => 150],
            ['category' => 'investment', 'name' => 'VIP', 'amount_min' => 1800, 'amount_max' => 9999, 'img_url' => '', 'periodicity' => 'annual', 'membership' => 250],
            
            // Planes de cobertura
            ['category' => 'coverage', 'name' => 'Cobertura de Deportes', 'amount_min' => 50, 'amount_max' => 500, 'img_url' => '', 'periodicity' => 'annual', 'membership' => 0],
        ];

        foreach ($plans as $plan) {
            Plans::firstOrCreate(
                ['name' => $plan['name']], // condición para evitar duplicados
                $plan
            );
        }
    }
}

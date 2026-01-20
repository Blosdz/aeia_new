<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fund;

class FundSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $funds = [
            [
                'category' => 'coverage',
                'name' => 'Fondo General de Cobertura',
                'initial_amount' => 0,
                'current_amount' => 0,
                'status' => 'open',
                'metadata' => json_encode([
                    'description' => 'Fondo general para coberturas de seguros',
                    'risk_level' => 'low',
                ]),
            ],
            [
                'category' => 'investment',
                'name' => 'Fondo General de Inversión',
                'initial_amount' => 0,
                'current_amount' => 0,
                'status' => 'open',
                'metadata' => json_encode([
                    'description' => 'Fondo general para inversiones',
                    'risk_level' => 'medium',
                ]),
            ],
            [
                'category' => 'investment',
                'name' => 'Fondo de Alto Rendimiento',
                'initial_amount' => 0,
                'current_amount' => 0,
                'status' => 'open',
                'metadata' => json_encode([
                    'description' => 'Fondo de inversión de alto riesgo y alto rendimiento',
                    'risk_level' => 'high',
                ]),
            ],
        ];

        foreach ($funds as $fundData) {
            Fund::firstOrCreate(
                [
                    'category' => $fundData['category'],
                    'name' => $fundData['name'],
                ],
                $fundData
            );
        }

        $this->command->info('Fondos creados exitosamente!');
    }
}

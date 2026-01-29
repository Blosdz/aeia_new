<?php

namespace Database\Seeders;

use App\Models\Plans;
use Illuminate\Database\Seeder;

class AdminCommissionPlanSeeder extends Seeder
{
    /**
     * Seed the admin commission plan type
     */
    public function run(): void
    {
        Plans::firstOrCreate(
            [
                'category' => 'investment',
                'name' => 'Admin Commission',
            ],
            [
                'amount_min' => 0,
                'amount_max' => 0,
                'periodicity' => 'monthly',
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ReferralCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereNull('referral_code')->get();

        foreach ($users as $user) {
            $code = 'REF_' . strtoupper(substr($user->unique_code ?? $user->email, 0, 6)) . '_' . $user->id;
            $user->update(['referral_code' => $code]);
        }

        $this->command->info("✅ Referral codes generated for " . $users->count() . " users!");
    }
}

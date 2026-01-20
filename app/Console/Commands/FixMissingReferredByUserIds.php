<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class FixMissingReferredByUserIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referral:fix-missing-user-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca usuarios sin referred_by_user_id y los asigna basado en su historial o patrones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Buscando usuarios sin referred_by_user_id...');

        // Obtener todos los usuarios que NO tienen referred_by_user_id asignado (excepto el admin)
        $usersWithoutReferrer = User::where('id', '!=', 1)
            ->whereNull('referred_by_user_id')
            ->orderBy('created_at')
            ->get();

        $fixed = 0;
        $skipped = 0;

        foreach ($usersWithoutReferrer as $user) {
            $this->line("─────────────────────────────────────────────");
            $this->line("Usuario ID: {$user->id}");
            $this->line("Email: {$user->email}");
            $this->line("Creado: {$user->created_at}");
            $this->line("Código: {$user->referral_code}");
            
            // Buscar un referrador potencial
            // Estrategia: buscar usuarios creados ANTES que este usuario
            // que tengan un referral_code similar o que coincida con el patrón
            
            $potentialReferrers = User::where('id', '!=', $user->id)
                ->where('created_at', '<', $user->created_at)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            if ($potentialReferrers->isEmpty()) {
                // Si no hay usuarios anteriores, incluir al admin como opción
                $potentialReferrers = User::where('id', 1)->get();
            }

            $this->line("  📋 Referradores potenciales:");
            foreach ($potentialReferrers as $index => $referrer) {
                $choice = $index + 1;
                $this->line("    {$choice}. {$referrer->name} (ID: {$referrer->id}) - {$referrer->referral_code}");
            }

            // Preguntar al usuario
            $choice = $this->ask('¿Cuál es el referrador? (ingresa el número, o presiona Enter para saltar)', 0);

            if ($choice === '0' || $choice === '') {
                $this->line("  ⏭️  Saltando usuario...");
                $skipped++;
                continue;
            }

            $choiceIndex = (int)$choice - 1;
            if ($choiceIndex < 0 || $choiceIndex >= count($potentialReferrers)) {
                $this->line("  ❌ Opción inválida");
                $skipped++;
                continue;
            }

            $selectedReferrer = $potentialReferrers[$choiceIndex];
            
            // Actualizar el usuario
            $user->update([
                'referred_by_user_id' => $selectedReferrer->id,
                'referral_accepted_at' => now(),
            ]);

            $this->line("  ✅ Referrador asignado: {$selectedReferrer->name} (ID: {$selectedReferrer->id})");
            $fixed++;
        }

        $this->info("─────────────────────────────────────────────");
        $this->info("✅ Proceso completado.");
        $this->info("  • Se fijaron: $fixed usuarios");
        $this->info("  • Se saltaron: $skipped usuarios");
    }
}

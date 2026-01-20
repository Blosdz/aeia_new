<?php

namespace App\Console\Commands;

use App\Models\Fund;
use App\Services\ProjectClosureService;
use Illuminate\Console\Command;

class CloseFundPeriod extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fund:close-period 
                            {fund_id : ID del fondo a cerrar}
                            {--period-start= : Fecha de inicio del período (YYYY-MM-DD)}
                            {--period-end= : Fecha de fin del período (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cerrar un período de un fondo y distribuir ganancias entre clientes y referidos';

    protected ProjectClosureService $closureService;

    public function __construct(ProjectClosureService $closureService)
    {
        parent::__construct();
        $this->closureService = $closureService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $fundId = $this->argument('fund_id');
        
        // Obtener el fondo
        $fund = Fund::find($fundId);
        if (!$fund) {
            $this->error("Fondo con ID {$fundId} no encontrado");
            return 1;
        }

        // Preparar opciones
        $options = [];
        if ($this->option('period-start')) {
            $options['period_start'] = $this->option('period-start');
        }
        if ($this->option('period-end')) {
            $options['period_end'] = $this->option('period-end');
        }

        try {
            $this->info("Cerrando período del fondo: {$fund->name}...");

            $closure = $this->closureService->closeProjectPeriod($fund, $options);

            // Mostrar resumen
            $this->newLine();
            $this->info('✓ Período cerrado exitosamente');
            $this->newLine();
            
            $this->table(
                ['Concepto', 'Monto'],
                [
                    ['Total Inversión', '$' . number_format($closure->total_investment, 2)],
                    ['Ganancia Total', '$' . number_format($closure->total_earnings, 2)],
                    ['Empresa (20%)', '$' . number_format($closure->company_total, 2)],
                    ['Referidos (15% 1er pago)', '$' . number_format($closure->referrals_total, 2)],
                    ['Clientes', '$' . number_format($closure->clients_earnings, 2)],
                    ['Total Clientes', $closure->total_clients],
                    ['Referidos Comisionados', $closure->first_deposits_referred],
                ]
            );

            $this->newLine();
            $this->info("Período: {$closure->period_start} a {$closure->period_end}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Error al cerrar el período: {$e->getMessage()}");
            return 1;
        }
    }
}

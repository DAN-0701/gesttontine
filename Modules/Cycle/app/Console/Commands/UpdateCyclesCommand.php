<?php

namespace Modules\Cycle\Console\Commands;

use Illuminate\Console\Command;
use Modules\Cycle\Http\Controllers\CycleController;

class UpdateCyclesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cycles:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Traitement automatique des cycles et des tours (démarrage, passage de tour, clôture)';

    /**
     * Execute the console command.
     */
    public function handle(CycleController $cycleController): int
    {
        $this->info("Début du traitement automatique des cycles et des tours...");

        // Appel direct des méthodes métier situées dans CycleController (sans Service ni Repository)
        $demarres = $cycleController->traiterDemarrageAutomatique();
        $this->line("-> Cycles démarrés automatiquement : {$demarres}");

        $toursTraites = $cycleController->traiterAvancementToursAutomatique();
        $this->line("-> Tours clôturés et avancés : {$toursTraites}");

        $clotures = $cycleController->traiterClotureCyclesAutomatique();
        $this->line("-> Cycles clôturés : {$clotures}");

        $this->info("Traitement automatique terminé avec succès.");

        return Command::SUCCESS;
    }
}

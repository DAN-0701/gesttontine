<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Planification automatique de la commande cycles:update (Laravel 12)
|--------------------------------------------------------------------------
| Exécution toutes les minutes pour traiter le démarrage automatique,
| l'avancement des tours et la clôture automatique des cycles.
*/
Schedule::command('cycles:update')->everyMinute();

<?php

use Illuminate\Support\Facades\Route;
use Modules\Cycle\Http\Controllers\CycleController;
use Modules\Cycle\Http\Controllers\ToursController;

Route::get('/dashboard', [CycleController::class, 'dashboard'])->name('dashboard');

Route::resource('cycles', CycleController::class);

Route::post('/cycles/{cycle}/membres', [CycleController::class, 'ajoutermembres'])->name('cycles.ajouterMembres');

Route::put('/cycles/{cycle}/rangs', [CycleController::class, 'modifierrangs'])->name('cycles.modifierRangs');

Route::post('/cycles/{cycle}/demarrer', [CycleController::class, 'demarrer'])->name('cycles.demarrer');

Route::post('/cycles/{cycle}/cloturer', [CycleController::class, 'cloturer'])->name('cycles.cloturer');

Route::get('/cycles/{cycle}/tableau', [CycleController::class, 'show'])->name('cycles.tableau');

Route::get('/tours', [ToursController::class, 'index']);

Route::get('/tours/{tour}', [ToursController::class, 'show']);

Route::get('/cycles/{cycle}/tours', [ToursController::class, 'toursCycle']);

Route::post('/tours/{tour}/distribuer', [ToursController::class, 'distribuer'])->name('tours.distribuer');

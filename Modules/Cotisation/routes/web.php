<?php

use Illuminate\Support\Facades\Route;
use Modules\Cotisation\Http\Controllers\CotisationController;
use Modules\Cotisation\Http\Controllers\PaiementController;
use Modules\Cotisation\Http\Controllers\PenaliteController;

Route::resource('cotisations', CotisationController::class)->only(['index', 'show'])->names('cotisation');

Route::get('/cotisations/{cotisation}/paiement', [PaiementController::class, 'create'])->name('paiements.create');
Route::post('/cotisations/{cotisation}/paiement', [PaiementController::class, 'store'])->name('paiements.store');

Route::get('/tours/{tour}/cotisations', [CotisationController::class, 'tour']);

Route::post('/paiements', [PaiementController::class, 'store']);
Route::post('/paiements/{paiement}/annuler', [PaiementController::class, 'annuler'])->name('paiements.annuler');

Route::post('/penalites', [PenaliteController::class, 'store']);
Route::post('/penalites/{penalite}/payer', [PenaliteController::class, 'payer'])->name('penalites.payer');
Route::post('/penalites/{penalite}/annuler', [PenaliteController::class, 'annuler'])->name('penalites.annuler');

<?php

use Illuminate\Support\Facades\Route;
use Modules\Cotisation\Http\Controllers\CotisationController;
use Modules\Cotisation\Http\Controllers\PaiementController;
use Modules\Cotisation\Http\Controllers\PenaliteController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('cotisations', CotisationController::class)->names('cotisation');
});

Route::get('/cotisations',[CotisationController::class,'index']);

Route::get('/cotisations/{cotisation}',[CotisationController::class,'show']);

Route::get('/tours/{tour}/cotisations',[CotisationController::class,'tour']);

Route::post('/paiements',[PaiementController::class,'store']);

Route::post('/penalites',[PenaliteController::class,'store']);

Route::post('/penalites/{penalite}/payer',[PenaliteController::class,'payer']);

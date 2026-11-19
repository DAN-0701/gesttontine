<?php

use Illuminate\Support\Facades\Route;
use Modules\Cotisation\Http\Controllers\CotisationController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('cotisations', CotisationController::class)->only(['index', 'show'])->names('api.cotisation');
});

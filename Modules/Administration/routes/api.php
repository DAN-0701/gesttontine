<?php

use Illuminate\Support\Facades\Route;
use Modules\Administration\Http\Controllers\AdministrationController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('administrations', [AdministrationController::class, 'index'])->name('api.administration.index');
    Route::post('administrations/permissions', [AdministrationController::class, 'setPermission'])->name('api.administration.permissions.set');
    Route::post('administrations/permissions/reset', [AdministrationController::class, 'resetPermission'])->name('api.administration.permissions.reset');
});

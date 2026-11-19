<?php

use Illuminate\Support\Facades\Route;
use Modules\Administration\Http\Controllers\AdministrationController;
use Modules\Administration\Http\Controllers\NotificationController;

Route::middleware('web')->group(function () {
    Route::get('/administration', [AdministrationController::class, 'index'])->name('administration.index');
    Route::post('/administration/permissions', [AdministrationController::class, 'setPermission'])->name('administration.permissions.set');
    Route::post('/administration/permissions/reset', [AdministrationController::class, 'resetPermission'])->name('administration.permissions.reset');

    // Routes notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/lue', [NotificationController::class, 'marquerLue'])->name('notifications.lue');
    Route::post('/notifications/lire-toutes', [NotificationController::class, 'marquerToutesLues'])->name('notifications.lire-toutes');
});

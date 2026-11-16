<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\CycleController;
use App\Http\Controllers\CycleTableController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaiementController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Members
    Route::get('/membres', [MemberController::class, 'index'])->name('membres.index');
    Route::get('/membres/create', [MemberController::class, 'create'])->name('membres.create');
    Route::post('/membres', [MemberController::class, 'store'])->name('membres.store');
    Route::get('/membres/{member}/edit', [MemberController::class, 'edit'])->name('membres.edit');
    Route::put('/membres/{member}', [MemberController::class, 'update'])->name('membres.update');

    // Cycles
    Route::get('/cycles', [CycleController::class, 'index'])->name('cycles.index');
    Route::get('/cycles/create', [CycleController::class, 'create'])->name('cycles.create');
    Route::post('/cycles', [CycleController::class, 'store'])->name('cycles.store');
    Route::get('/cycles/{cycle}/edit', [CycleController::class, 'edit'])->name('cycles.edit');
    Route::put('/cycles/{cycle}', [CycleController::class, 'update'])->name('cycles.update');
    Route::post('/cycles/{cycle}/membres', [CycleController::class, 'ajouterMembres'])->name('cycles.ajouterMembres');
    Route::put('/cycles/{cycle}/rangs', [CycleController::class, 'modifierRangs'])->name('cycles.modifierRangs');
    Route::post('/cycles/{cycle}/demarrer', [CycleController::class, 'demarrer'])->name('cycles.demarrer');
    Route::post('/cycles/{cycle}/retirer-membre', [CycleController::class, 'retirerMembre'])->name('cycles.retirerMembre');

    // Cycle table (tableau du cycle)
    Route::get('/cycles/{cycle}/tableau', [CycleTableController::class, 'show'])->name('cycles.tableau');
    Route::post('/cycles/{cycle}/tours/{tour}/cloturer', [CycleTableController::class, 'cloturerTour'])->name('tours.cloturer');
    Route::post('/cycles/{cycle}/tours/{tour}/distribuer', [CycleTableController::class, 'distribuer'])->name('tours.distribuer');

    // Paiements
    Route::get('/cotisations/{cotisation}/paiement', [PaiementController::class, 'create'])->name('paiements.create');
    Route::post('/cotisations/{cotisation}/paiement', [PaiementController::class, 'store'])->name('paiements.store');

    // Users
    Route::get('/utilisateurs', [UserController::class, 'index'])->name('utilisateurs.index');
    Route::post('/utilisateurs', [UserController::class, 'store'])->name('utilisateurs.store');
});

<?php

use App\Http\Controllers\user;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Routes Authentification & Utilisateurs (app/Http/Controllers/user.php)
|--------------------------------------------------------------------------
| Seul le modèle User et son contrôleur restent hors des modules nwidart.
*/
Route::get('/', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::post('/login', [user::class, 'login']);
Route::post('/logout', [user::class, 'logout'])->name('logout');
Route::post('/update', [user::class, 'update'])->name('password.update');
Route::post('/users/{user}/updateall', [user::class, 'updateall'])->name('users.updateall');

Route::get('/utilisateurs', [user::class, 'index'])->name('utilisateurs.index');
Route::post('/utilisateurs', [user::class, 'store'])->name('utilisateurs.store');

Route::post('/password/forgot', [\App\Http\Controllers\PasswordResetController::class, 'forgotPassword'])->name('password.forgot');
Route::get('/mot-de-passe-obligatoire', [\App\Http\Controllers\PasswordResetController::class, 'showChangeRequired'])->name('password.change-required');
Route::post('/mot-de-passe-obligatoire', [\App\Http\Controllers\PasswordResetController::class, 'updateChangeRequired'])->name('password.change-required.update');

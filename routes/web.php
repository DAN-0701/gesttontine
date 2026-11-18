<?php

use App\Http\Controllers\user;
use App\Models\administrateur;
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

Route::get('/utilisateurs', [user::class, 'index'])->name('utilisateurs.index');
Route::post('/utilisateurs', [user::class, 'store'])->name('utilisateurs.store');

<?php

use Illuminate\Support\Facades\Route;
use Modules\Members\Http\Controllers\MembersController;

Route::post('/members/import', [MembersController::class, 'import'])->name('members.import');
Route::post('/members/{member}/toggle-statut', [MembersController::class, 'toggleStatut'])->name('members.toggle-statut');

Route::resource('members', MembersController::class)->names('members');
Route::resource('membres', MembersController::class)->names('membres');

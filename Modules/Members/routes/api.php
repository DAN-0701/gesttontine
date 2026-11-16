<?php

use Illuminate\Support\Facades\Route;
use Modules\Members\Http\Controllers\MembersController;

Route::resource('members', MembersController::class);
/*Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('members', MembersController::class)->names('members');
});*/

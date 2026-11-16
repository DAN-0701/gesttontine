<?php

use Illuminate\Support\Facades\Route;
use Modules\Members\Http\Controllers\MembersController;

Route::resource('members', MembersController::class)->names('members');
Route::resource('membres', MembersController::class)->names('membres');

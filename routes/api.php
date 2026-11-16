<?php

use App\Http\Controllers\user;
use Illuminate\Support\Facades\Route;
Route::post('/login', [user::class,'login']);
Route::post('/logout', [user::class,'logout']);
Route::put('/update', [user::class,'update']);

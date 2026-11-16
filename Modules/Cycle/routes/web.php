<?php

use Illuminate\Support\Facades\Route;
use Modules\Cycle\Http\Controllers\CycleController;
use Modules\Cycle\Http\Controllers\ToursController;
Route::middleware(['auth','gestroleuser'])->group(function () {

    Route::resource('cycles',CycleController::class);

    Route::post('/cycles/{cycle}/membres',[CycleController::class,'ajoutermembres']);

    Route::put('/cycles/{cycle}/rangs',[CycleController::class,'modifierrangs']);

    Route::post('/cycles/{cycle}/demarrer',[CycleController::class,'demarrer']);

    Route::post('/cycles/{cycle}/cloturer',[CycleController::class,'cloturer']);

});
Route::middleware(['auth','gestroleuser'])->group(function(){

    Route::get('/tours',[ToursController::class,'index']);

    Route::get('/tours/{tour}',[ToursController::class,'show']);

    Route::get('/cycles/{cycle}/tours',[ToursController::class,'toursCycle']);

    Route::post('/tours/{tour}/distribuer',[ToursController::class,'distribuer']);

});

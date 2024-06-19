<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\InputController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/input', [InputController::class, 'input']);
Route::post('/input', [InputController::class, 'inputPost']);

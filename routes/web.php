<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('guest');
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth');

Route::view('/{any?}', 'app')->where('any', '^(?!api|sanctum).*$');

<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// --- Auth ---
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('web');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('web');
Route::get('/auth/check', [AuthController::class, 'check'])->middleware('web');
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('web');

Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['status' => 'ok']);
})->middleware('web');

Route::get('/', function () {
    return redirect('/dashboard.html');
});

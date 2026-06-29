<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('amazon-ads')->group(function () {
    Route::get('/dashboard/campaigns', [DashboardController::class, 'campaigns']);
    Route::get('/dashboard/daily', [DashboardController::class, 'daily']);
});

<?php

use App\Modules\Reports\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('reports')->group(function () {

    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [DashboardController::class, 'summary'])->name('reports.dashboard.summary');
        Route::get('/top-products', [DashboardController::class, 'topProducts'])->name('reports.dashboard.top-products');
        Route::get('/chart', [DashboardController::class, 'salesChart'])->name('reports.dashboard.chart');
    });

});

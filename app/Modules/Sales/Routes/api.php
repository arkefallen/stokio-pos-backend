<?php

use App\Modules\Sales\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Sales (POS)
    Route::prefix('sales')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('sales.index');
        Route::post('/', [SaleController::class, 'store'])->name('sales.store');
        Route::get('/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::post('/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');
    });
});

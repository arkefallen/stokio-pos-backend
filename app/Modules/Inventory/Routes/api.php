<?php

use App\Modules\Inventory\Http\Controllers\InventoryController;
use App\Modules\Inventory\Http\Controllers\StockAdjustmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('inventory')->group(function () {

    // Create Stock Adjustment (Opname)
    Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->name('inventory.adjustments.store');

    // View History (Kartu Stok)
    Route::get('/history', [InventoryController::class, 'history'])->name('inventory.history');

});

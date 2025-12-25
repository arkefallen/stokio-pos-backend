<?php

use App\Modules\Auth\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Module Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    // Public routes (guest)
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

    // Protected routes (authenticated)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');

        // Admin only
        Route::get('/users', [AuthController::class, 'index'])->name('auth.users.index');
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
        Route::delete('/users/{user}', [AuthController::class, 'destroy'])->name('auth.users.destroy');
    });
});


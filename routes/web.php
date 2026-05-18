<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AdminAuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\UserManagementController;
use App\Http\Controllers\Web\ParkingHistoryController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function () {

    // Rute Guest (Hanya untuk yang belum login)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
    });

    // Rute Admin (Harus login dan role=admin)
    Route::middleware(['auth', 'admin.web'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Manajemen Pengguna (Users)
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users/import', [UserManagementController::class, 'import'])->name('users.import');
        Route::get('/users/export', [UserManagementController::class, 'export'])->name('users.export');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

        // Riwayat Parkir (Parking History)
        Route::get('/parking-history', [ParkingHistoryController::class, 'index'])->name('parking-history.index');
        Route::get('/parking-history/{parking}', [ParkingHistoryController::class, 'show'])->name('parking-history.show');
    });
});

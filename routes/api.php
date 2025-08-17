<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BroadcastController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ParkingController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VehicleController;
use App\Http\Controllers\API\SingleController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login-admin', [AuthController::class, 'loginAdmin'])->name('login-admin');

// Public endpoints for external systems (OCR, etc.)
// Route::post('send-notification/{user}', [NotificationController::class, 'sendNotification'])->name('send-notification');
Route::post('verify-license-plate', [ParkingController::class, 'verifyLicensePlate'])->name('verify-license-plate');
Route::put('fuzzy-check-out', [ParkingController::class, 'fuzzyCheckOut'])->name('fuzzy-check-out');
Route::post('record-event', [ParkingController::class, 'recordEvent'])->name('record-event');
Route::post('check-in', [ParkingController::class, 'checkIn'])->name('check-in');
Route::put('check-out', [ParkingController::class, 'checkOut'])->name('check-out')->middleware('auth:sanctum');

// Protected routes - authenticated users
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('change-password', [AuthController::class, 'changePassword'])->name('change-password');

    // Parking operations
    Route::post('parking/confirm-check-out', [ParkingController::class, 'confirmCheckOut'])->name('confirm-check-out');
    Route::post('parking/is-check-in', [ParkingController::class, 'isUserCheckIn'])->name('is-user-checkin');
    Route::post('parking/report-check-out', [ParkingController::class, 'reportCheckOut'])->name('report-check-out');
    Route::get('parking', [ParkingController::class, 'getUserParkingRecords'])->name('parking');

    // Vehicle management
    Route::apiResource('vehicle', VehicleController::class);

    // Broadcast management
    Route::apiResource('broadcast', BroadcastController::class);

    // User profile
    Route::put('user/{user}/fcm-token', [UserController::class, 'updateFcmToken'])->name('user.fcm');

    // Public data (all authenticated users can access)
    Route::get('broadcast-all', [SingleController::class, 'getAllBroadcasts'])->name('broadcast.all');
    Route::get('vehicle-all', [SingleController::class, 'getAllVehicles'])->name('vehicle.all');

    // Admin-only routes
    Route::middleware(EnsureUserIsAdmin::class)->group(function () {
        // Dashboard
        Route::get('dashboard', [ParkingController::class, 'getDashboard'])->name('dashboard');

        // User management - Complete CRUD operations
        Route::get('user', [UserController::class, 'index'])->name('user.index');
        Route::post('user', [UserController::class, 'store'])->name('user.store');
        Route::get('user/{user}', [UserController::class, 'show'])->name('user.show');
        Route::put('user/{user}', [UserController::class, 'update'])->name('user.update');
        Route::delete('user/{user}', [UserController::class, 'delete'])->name('user.delete');
        Route::post('user/import', [UserController::class, 'import'])->name('user.import');

        // All parking records
        Route::get('parking-all', [ParkingController::class, 'getParkingRecords'])->name('parking-all');
        Route::get('parking-details/{id}', [ParkingController::class, 'getParkingDetails'])->name('parking-details');

        // Enhanced parking history for admin dashboard
        Route::get('parking-history-details', [ParkingController::class, 'getParkingHistoryDetails'])->name('parking-history-details');
    });
});

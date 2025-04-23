<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BroadcastController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ParkingController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VehicleController;
use App\Http\Controllers\API\SingleController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login-admin', [AuthController::class, 'loginAdmin'])->name('login-admin');

// Grouping routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // dashboard
    Route::get('dashboard', [ParkingController::class, 'getDashboard'])->name('dashboard');

    // broadcast
    Route::apiResource('broadcast', BroadcastController::class);

    // parking
    Route::post('check-in', [ParkingController::class, 'checkIn'])->name('check-in');
    Route::put('check-out', [ParkingController::class, 'checkOut'])->name('check-out');
    Route::get('parking', [ParkingController::class, 'getUserParkingRecords'])->name('parking');
    Route::get('parking-all', [ParkingController::class, 'getParkingRecords'])->name('parking-all');
    Route::post('parking/confirm-check-out', [ParkingController::class, 'confirmCheckOut'])->name('confirm-check-out');
    Route::post('parking/is-check-in', [ParkingController::class, 'isUserCheckIn'])->name('is-user-checkin');

    // user
    Route::get('user', [UserController::class, 'index'])->name('user');
    Route::patch('user/{user}/fcm-token', [UserController::class, 'updateFcmToken'])->name('user.fcm');
    Route::post('user/import', [UserController::class, 'import'])->name('user.import');
    Route::delete('user/{user}', [UserController::class, 'delete'])->name('user.delete');

    Route::post('change-password', [AuthController::class, 'changePassword'])->name('change-password');

    // vehicle
    Route::apiResource('vehicle', VehicleController::class);
    Route::get('vehicle-all', [SingleController::class, 'getAllVehicles'])->name('vehicle.all');
});

Route::post('send-notification', [NotificationController::class, 'sendNotification'])->name('send-notification');

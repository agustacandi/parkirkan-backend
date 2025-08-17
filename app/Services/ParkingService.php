<?php

namespace App\Services;

use App\Models\Parking;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;

class ParkingService
{
    public function __construct(
        private LicensePlateMatchingService $matchingService
    ) {}

    /**
     * Check if user has active parking session for a vehicle
     */
    public function hasActiveParkingSession(int $vehicleId, int $userId): bool
    {
        return Parking::where('vehicle_id', $vehicleId)
            ->where('user_id', $userId)
            ->whereNull('check_out_time')
            ->exists();
    }

    /**
     * Check if check-out is confirmed for a vehicle
     */
    public function isCheckOutConfirmed(int $vehicleId, int $userId): bool
    {
        return Parking::where('vehicle_id', $vehicleId)
            ->where('user_id', $userId)
            ->whereNull('check_out_time')
            ->where('is_check_out_confirmed', true)
            ->exists();
    }

    /**
     * Get active parking record for a vehicle
     */
    public function getActiveParkingRecord(int $vehicleId, int $userId): ?Parking
    {
        return Parking::where('vehicle_id', $vehicleId)
            ->where('user_id', $userId)
            ->whereNull('check_out_time')
            ->first();
    }

    /**
     * Create check-in record
     */
    public function createCheckIn(Vehicle $vehicle, string $imagePath): Parking
    {
        return Parking::create([
            'user_id' => Auth::id(),
            'vehicle_id' => $vehicle->id,
            'check_in_time' => now(),
            'check_in_image' => $imagePath,
        ]);
    }

    /**
     * Update parking record for check-out
     */
    public function updateCheckOut(Parking $parking, string $imagePath): Parking
    {
        $parking->update([
            'check_out_time' => now(),
            'check_out_image' => $imagePath,
            'status' => 'done',
        ]);

        return $parking;
    }

    /**
     * Confirm check-out for a vehicle
     */
    public function confirmCheckOut(Vehicle $vehicle, int $userId): bool
    {
        return Parking::where('vehicle_id', $vehicle->id)
            ->where('user_id', $userId)
            ->whereNull('check_out_time')
            ->update(['is_check_out_confirmed' => true]) > 0;
    }

    /**
     * Store uploaded image and return the filename
     */
    public function storeImage($image, string $directory): string
    {
        $filename = $image->hashName();
        $image->storeAs($directory, $filename);
        return $filename;
    }

    /**
     * Send check-out alert notification
     */
    public function sendCheckOutAlert(Vehicle $vehicle): void
    {
        $vehicle->load("user");
        $user = $vehicle->user;
        $notification = Notification::create(
            '🚨 ATTENTION!',
            'Someone is trying to check out your vehicle!'
        );

        $notificationData = [
            'notification_type' => 'alert',
            'click_action' => 'OPEN_NOTIFICATION',
            'vehicle_license_plate' => (string) $vehicle->license_plate,
        ];

        $notificationService = new NotificationService($notification, $notificationData);
        $notificationService->sendToToken($user->fcm_token, AndroidConfig::fromArray([
            'priority' => 'high',
            'notification' => [
                'channel_id' => 'parking_alert_channel',
            ],
        ]));
    }

    /**
     * Report check-out for a vehicle
     */
    public function reportCheckOut(Vehicle $vehicle): void
    {
        $notification = Notification::create(
            '🚨 SECURITY ALERT!',
            'A user’s vehicle with license plate ' . (string) $vehicle->license_plate . ' is being driven away without authorization!'
        );

        $notificationData = [
            'notification_type' => 'alert',
            'click_action' => 'OPEN_NOTIFICATION',
            'vehicle_license_plate' => (string) $vehicle->license_plate,
        ];

        $notificationService = new NotificationService($notification, $notificationData);
        $notificationService->sendToTopic('alert', AndroidConfig::fromArray([
            'priority' => 'high',
            'notification' => [
                'channel_id' => 'parking_alert_channel',
            ],
        ]));
    }

    /**
     * Find vehicle by license plate with matching strategy
     */
    public function findVehicleByLicensePlate(
        string $licensePlate,
        string $verificationMode = 'exact',
        float $confidenceThreshold = 0.7
    ): array {
        if ($verificationMode === 'exact') {
            $vehicle = $this->matchingService->findExactMatch($licensePlate);
            return [
                'vehicle' => $vehicle,
                'score' => $vehicle ? 1.0 : 0.0,
                'method' => 'exact'
            ];
        }

        $result = $this->matchingService->findSimilarLicensePlate($licensePlate, $confidenceThreshold);
        return [
            'vehicle' => $result['match'],
            'score' => $result['score'],
            'method' => 'fuzzy'
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Parking>
 */
class ParkingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkInTime = $this->faker->dateTimeBetween('-30 days', 'now');
        $hasCheckedOut = $this->faker->boolean(70); // 70% chance of being checked out

        $checkOutTime = null;
        $checkOutImage = null;
        $isCheckOutConfirmed = false;
        $status = 'parked';

        if ($hasCheckedOut) {
            // Check out between 30 minutes to 8 hours after check in
            $checkOutTime = Carbon::createFromTimestamp($checkInTime->getTimestamp())
                ->addMinutes($this->faker->numberBetween(30, 480));
            $checkOutImage = 'default-checkout.jpg';
            $isCheckOutConfirmed = $this->faker->boolean(90); // 90% of checkouts are confirmed
            $status = $isCheckOutConfirmed ? 'checked_out' : 'pending_checkout';
        }

        return [
            'check_in_time' => $checkInTime,
            'check_in_image' => 'default-checkin.jpg',
            'check_out_time' => $checkOutTime,
            'check_out_image' => $checkOutImage,
            'status' => $status,
            'is_check_out_confirmed' => $isCheckOutConfirmed,
            'vehicle_id' => Vehicle::factory(),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Create a currently parked vehicle (no checkout).
     */
    public function currentlyParked(): static
    {
        return $this->state(fn(array $attributes) => [
            'check_in_time' => $this->faker->dateTimeBetween('-3 days', 'now'),
            'check_out_time' => null,
            'check_out_image' => null,
            'status' => 'parked',
            'is_check_out_confirmed' => false,
        ]);
    }

    /**
     * Create a completed parking session.
     */
    public function completed(): static
    {
        $checkInTime = $this->faker->dateTimeBetween('-30 days', '-1 days');
        $checkOutTime = Carbon::createFromTimestamp($checkInTime->getTimestamp())
            ->addMinutes($this->faker->numberBetween(30, 480));

        return $this->state(fn(array $attributes) => [
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'check_out_image' => 'default-checkout.jpg',
            'status' => 'checked_out',
            'is_check_out_confirmed' => true,
        ]);
    }

    /**
     * Create a pending checkout parking session.
     */
    public function pendingCheckout(): static
    {
        $checkInTime = $this->faker->dateTimeBetween('-3 days', '-1 hours');
        $checkOutTime = Carbon::createFromTimestamp($checkInTime->getTimestamp())
            ->addMinutes($this->faker->numberBetween(30, 480));

        return $this->state(fn(array $attributes) => [
            'check_in_time' => $checkInTime,
            'check_out_time' => $checkOutTime,
            'check_out_image' => 'default-checkout.jpg',
            'status' => 'pending_checkout',
            'is_check_out_confirmed' => false,
        ]);
    }

    /**
     * Create parking with specific user and vehicle.
     */
    public function forUserAndVehicle(User $user, Vehicle $vehicle): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
        ]);
    }
}

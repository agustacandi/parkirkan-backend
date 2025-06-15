<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Parking;
use Illuminate\Database\Seeder;

class ParkingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicles = Vehicle::with('user')->get();

        if ($vehicles->isEmpty()) {
            $this->command->warn('No vehicles found. Please run VehicleSeeder first.');
            return;
        }

        $this->command->info('Creating parking history...');

        // Create various parking scenarios
        $totalParkings = 0;

        foreach ($vehicles as $vehicle) {
            $user = $vehicle->user;

            // Each vehicle gets 3-8 historical parking sessions
            $historicalCount = rand(3, 8);

            for ($i = 0; $i < $historicalCount; $i++) {
                Parking::factory()->completed()->forUserAndVehicle($user, $vehicle)->create();
                $totalParkings++;
            }

            // 30% chance of having a currently parked vehicle
            if (rand(1, 10) <= 3) {
                Parking::factory()->currentlyParked()->forUserAndVehicle($user, $vehicle)->create();
                $totalParkings++;
            }

            // 20% chance of having a pending checkout
            if (rand(1, 10) <= 2) {
                Parking::factory()->pendingCheckout()->forUserAndVehicle($user, $vehicle)->create();
                $totalParkings++;
            }
        }

        // Create some additional random parking sessions
        Parking::factory(50)->completed()->create();
        Parking::factory(10)->currentlyParked()->create();
        Parking::factory(5)->pendingCheckout()->create();

        $totalParkings += 65;

        $this->command->info('✓ Parking history seeding completed!');
        $this->command->info('Total parking sessions created: ' . Parking::count());

        // Show breakdown by status
        $parked = Parking::where('status', 'parked')->count();
        $checkedOut = Parking::where('status', 'checked_out')->count();
        $pendingCheckout = Parking::where('status', 'pending_checkout')->count();

        $this->command->info("  - Currently parked: {$parked}");
        $this->command->info("  - Checked out: {$checkedOut}");
        $this->command->info("  - Pending checkout: {$pendingCheckout}");

        // Show date range
        $oldestParking = Parking::orderBy('check_in_time')->first();
        $newestParking = Parking::orderBy('check_in_time', 'desc')->first();

        if ($oldestParking && $newestParking) {
            $oldestDate = $oldestParking->check_in_time->format('Y-m-d');
            $newestDate = $newestParking->check_in_time->format('Y-m-d');
            $this->command->info("  - Date range: {$oldestDate} to {$newestDate}");
        }

        // Show average parking duration for completed sessions
        $completedSessions = Parking::where('status', 'checked_out')
            ->whereNotNull('check_out_time')
            ->get();

        if ($completedSessions->isNotEmpty()) {
            $totalMinutes = $completedSessions->sum(function ($parking) {
                return $parking->check_in_time->diffInMinutes($parking->check_out_time);
            });
            $averageMinutes = round($totalMinutes / $completedSessions->count());
            $averageHours = round($averageMinutes / 60, 1);

            $this->command->info("  - Average parking duration: " . $averageHours . " hours (" . $averageMinutes . " minutes)");
        }
    }
}

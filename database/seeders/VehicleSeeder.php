<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all existing users
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');
            return;
        }

        $this->command->info('Creating vehicles for existing users...');

        // Create vehicles for each user
        foreach ($users as $user) {
            // Each user gets 1-3 vehicles
            $vehicleCount = rand(1, 3);

            for ($i = 0; $i < $vehicleCount; $i++) {
                // Mix of cars and motorcycles
                $vehicleType = rand(1, 10) <= 7 ? 'motorcycle' : 'car'; // 70% motorcycles, 30% cars

                if ($vehicleType === 'motorcycle') {
                    Vehicle::factory()->motorcycle()->create([
                        'user_id' => $user->id,
                    ]);
                } else {
                    Vehicle::factory()->car()->create([
                        'user_id' => $user->id,
                    ]);
                }
            }
        }

        // Create some additional random vehicles
        Vehicle::factory(15)->create();

        $this->command->info('✓ Vehicle seeding completed!');
        $this->command->info('Total vehicles created: ' . Vehicle::count());

        // Show breakdown by type
        $motorcycles = Vehicle::whereIn('name', [
            'Honda Beat',
            'Yamaha NMAX',
            'Honda Vario',
            'Kawasaki Ninja',
            'Vespa Sprint',
            'Honda Scoopy',
            'Yamaha Mio',
            'Suzuki Satria',
            'Honda PCX',
            'Yamaha Aerox'
        ])->count();

        $cars = Vehicle::count() - $motorcycles;

        $this->command->info("  - Motorcycles: {$motorcycles}");
        $this->command->info("  - Cars: {$cars}");
    }
}

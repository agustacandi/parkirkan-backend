<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $vehicleNames = [
            'Honda Beat',
            'Yamaha NMAX',
            'Toyota Avanza',
            'Daihatsu Xenia',
            'Honda Vario',
            'Suzuki Ertiga',
            'Mitsubishi Pajero',
            'Honda Brio',
            'Kawasaki Ninja',
            'BMW X3',
            'Mercedes C-Class',
            'Toyota Innova',
            'Honda Civic',
            'Nissan Grand Livina',
            'Mazda CX-5',
            'Vespa Sprint',
            'Hyundai Tucson',
            'Isuzu Panther',
            'Ford Ranger',
            'Chevrolet Trax'
        ];

        // Generate Indonesian-style license plate
        $regions = ['B', 'D', 'F', 'G', 'H', 'K', 'L', 'M', 'N', 'R', 'S', 'T', 'W', 'Z'];
        $numbers = $this->faker->numberBetween(1000, 9999);
        $letters = $this->faker->randomLetter() . $this->faker->randomLetter() . $this->faker->randomLetter();
        $licensePlate = $this->faker->randomElement($regions) . ' ' . $numbers . ' ' . strtoupper($letters);

        return [
            'name' => $this->faker->randomElement($vehicleNames),
            'license_plate' => $licensePlate,
            'image' => 'default-vehicle.jpg', // Default image since we're not generating actual images
            'user_id' => User::factory(),
        ];
    }

    /**
     * Create a motorcycle variant.
     */
    public function motorcycle(): static
    {
        $motorcycles = [
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
        ];

        return $this->state(fn(array $attributes) => [
            'name' => $this->faker->randomElement($motorcycles),
        ]);
    }

    /**
     * Create a car variant.
     */
    public function car(): static
    {
        $cars = [
            'Toyota Avanza',
            'Daihatsu Xenia',
            'Suzuki Ertiga',
            'Mitsubishi Pajero',
            'Honda Brio',
            'BMW X3',
            'Mercedes C-Class',
            'Toyota Innova',
            'Honda Civic',
            'Nissan Grand Livina',
            'Mazda CX-5',
            'Hyundai Tucson',
            'Isuzu Panther',
            'Ford Ranger',
            'Chevrolet Trax'
        ];

        return $this->state(fn(array $attributes) => [
            'name' => $this->faker->randomElement($cars),
        ]);
    }
}

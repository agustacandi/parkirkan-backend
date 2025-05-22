<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@a.com',
        ]);

        User::factory()->create([
            'name' => 'Test User B',
            'email' => 'testb@a.com',
        ]);

        User::factory()->create([
            'name' => 'Test Security',
            'email' => 'sec@a.com',
            'role' => 'security',
        ]);

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'role' => 'admin',
            'password' => bcrypt('admin'),
        ]);
    }
}

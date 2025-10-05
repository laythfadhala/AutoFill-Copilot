<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Demo user for quick testing
        User::create([
            'name' => 'Demo User',
            'email' => 'demo@test.com',
            'password' => Hash::make('demo'),
        ]);

        // Test user with sample data
        User::create([
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Additional test user
        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->command->info('✅ Users seeded successfully!');
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;

class UserProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoUser = User::where('email', 'demo@test.com')->first();
        $testUser = User::where('email', 'test@example.com')->first();
        $janeUser = User::where('email', 'jane@example.com')->first();

        // Demo user profile
        if ($demoUser) {
            UserProfile::create([
                'user_id' => $demoUser->id,
                'name' => 'Personal Profile',
                'type' => 'personal',
                'data' => [
                    'firstName' => 'Demo',
                    'lastName' => 'User',
                    'email' => 'demo@test.com',
                    'phone' => '+1-555-0123',
                    'address' => '123 Main Street',
                    'city' => 'New York',
                    'state' => 'NY',
                    'zipCode' => '10001',
                    'country' => 'United States',
                ],
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        // Test user profiles
        if ($testUser) {
            UserProfile::create([
                'user_id' => $testUser->id,
                'name' => 'Professional Profile',
                'type' => 'professional',
                'data' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'john.doe@company.com',
                    'phone' => '+1-555-9876',
                    'address' => '456 Business Ave',
                    'city' => 'San Francisco',
                    'state' => 'CA',
                    'zipCode' => '94105',
                    'country' => 'United States',
                    'company' => 'TechCorp Inc',
                    'jobTitle' => 'Senior Engineer',
                    'website' => 'https://johndoe.dev',
                ],
                'is_default' => true,
                'is_active' => true,
            ]);

            UserProfile::create([
                'user_id' => $testUser->id,
                'name' => 'Shipping Profile',
                'type' => 'shipping',
                'data' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'address' => '789 Home Street',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'zipCode' => '90210',
                    'country' => 'United States',
                    'phone' => '+1-555-5555',
                ],
                'is_default' => false,
                'is_active' => true,
            ]);
        }

        // Jane user profile
        if ($janeUser) {
            UserProfile::create([
                'user_id' => $janeUser->id,
                'name' => 'Business Profile',
                'type' => 'business',
                'data' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                    'email' => 'jane.smith@business.com',
                    'phone' => '+1-555-7777',
                    'address' => '999 Corporate Blvd',
                    'city' => 'Austin',
                    'state' => 'TX',
                    'zipCode' => '73301',
                    'country' => 'United States',
                    'company' => 'Smith Enterprises',
                    'jobTitle' => 'CEO',
                ],
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ User profiles seeded successfully!');
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FormMapping;
use Illuminate\Database\Seeder;

class FormMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoUser = User::where('email', 'demo@test.com')->first();
        $testUser = User::where('email', 'test@example.com')->first();
        $janeUser = User::where('email', 'jane@example.com')->first();

        // GitHub signup form mapping
        if ($demoUser) {
            FormMapping::create([
                'user_id' => $demoUser->id,
                'domain' => 'github.com',
                'field_mappings' => [
                    'user[login]' => 'email',
                    'user[email]' => 'email',
                    'user[name]' => 'firstName lastName',
                ],
                'form_selector' => '#signup_form',
                'form_config' => [
                    'auto_submit' => false,
                    'confirm_before_fill' => true,
                ],
                'usage_count' => 15,
                'last_used_at' => now()->subDays(2),
            ]);
        }

        // Amazon checkout form mapping
        if ($testUser) {
            FormMapping::create([
                'user_id' => $testUser->id,
                'domain' => 'amazon.com',
                'field_mappings' => [
                    'address1' => 'address',
                    'city' => 'city',
                    'state' => 'state',
                    'postalCode' => 'zipCode',
                    'countryCode' => 'country',
                    'phoneNumber' => 'phone',
                ],
                'form_selector' => '.address-form',
                'form_config' => [
                    'auto_submit' => false,
                    'confirm_before_fill' => true,
                ],
                'usage_count' => 8,
                'last_used_at' => now()->subDays(1),
            ]);

            // LinkedIn profile form mapping
            FormMapping::create([
                'user_id' => $testUser->id,
                'domain' => 'linkedin.com',
                'field_mappings' => [
                    'firstName' => 'firstName',
                    'lastName' => 'lastName',
                    'email' => 'email',
                    'company' => 'company',
                    'position' => 'jobTitle',
                ],
                'form_selector' => '#profile-form',
                'form_config' => [
                    'auto_submit' => false,
                    'confirm_before_fill' => true,
                ],
                'usage_count' => 3,
                'last_used_at' => now()->subHours(6),
            ]);
        }

        // Google Forms mapping for Jane
        if ($janeUser) {
            FormMapping::create([
                'user_id' => $janeUser->id,
                'domain' => 'docs.google.com',
                'field_mappings' => [
                    'entry.123456789' => 'firstName',
                    'entry.987654321' => 'lastName',
                    'entry.555555555' => 'email',
                    'entry.111111111' => 'company',
                ],
                'form_selector' => '.freebirdFormviewerViewItemsContainerForm',
                'form_config' => [
                    'auto_submit' => false,
                    'confirm_before_fill' => true,
                ],
                'usage_count' => 12,
                'last_used_at' => now()->subHours(2),
            ]);
        }

        $this->command->info('✅ Form mappings seeded successfully!');
    }
}

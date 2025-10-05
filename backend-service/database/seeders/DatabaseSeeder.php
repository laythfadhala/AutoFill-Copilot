<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        // Call individual seeders in order
        $this->call([
            UserSeeder::class,
            UserProfileSeeder::class,
            FormMappingSeeder::class,
        ]);

        $this->command->info('🎉 Database seeding completed successfully!');
    }
}

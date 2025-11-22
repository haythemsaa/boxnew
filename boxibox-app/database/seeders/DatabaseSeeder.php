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
        $this->call([
            RolesPermissionsSeeder::class,
            DemoTenantSeeder::class,
            // DemoDataSeeder::class, // Uncomment to seed demo data
        ]);

        $this->command->info('✅ Database seeding completed successfully!');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Demo Tenant
        $tenant = Tenant::create([
            'name' => 'Demo Storage Company',
            'slug' => 'demo-storage',
            'domain' => 'demo.boxibox.local',
            'email' => 'contact@demo-storage.com',
            'phone' => '+33 1 23 45 67 89',
            'address' => '123 Rue de la Paix',
            'city' => 'Paris',
            'postal_code' => '75001',
            'country' => 'France',
            'plan' => 'professional',
            'max_sites' => 10,
            'max_boxes' => 500,
            'max_users' => 20,
            'is_active' => true,
            'trial_ends_at' => now()->addDays(30),
            'subscription_ends_at' => now()->addYear(),
            'settings' => [
                'currency' => 'EUR',
                'timezone' => 'Europe/Paris',
                'language' => 'fr',
                'date_format' => 'd/m/Y',
                'primary_color' => '#3b82f6',
                'secondary_color' => '#1e40af',
            ],
            'features' => [
                'floor_plan_editor',
                'dynamic_pricing',
                'email_notifications',
                'sms_notifications',
                'online_payments',
                'customer_portal',
            ],
        ]);

        // Create Subscription for Tenant
        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan' => 'professional',
            'billing_period' => 'monthly',
            'status' => 'active',
            'trial_ends_at' => now()->addDays(30),
            'started_at' => now(),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'amount' => 99.00,
            'discount' => 0,
            'currency' => 'EUR',
            'quantity_sites' => 10,
            'quantity_boxes' => 500,
            'quantity_users' => 20,
            'features' => [
                'floor_plan_editor',
                'dynamic_pricing',
                'email_notifications',
                'sms_notifications',
                'online_payments',
                'customer_portal',
            ],
        ]);

        // Create Tenant Admin User
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'John Doe',
            'email' => 'admin@demo-storage.com',
            'password' => Hash::make('password'),
            'phone' => '+33 6 12 34 56 78',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('tenant_admin');

        // Create Tenant Staff User
        $staff = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Jane Smith',
            'email' => 'staff@demo-storage.com',
            'password' => Hash::make('password'),
            'phone' => '+33 6 98 76 54 32',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $staff->assignRole('tenant_staff');

        $this->command->info('Demo tenant created successfully!');
        $this->command->info('Tenant Admin: admin@demo-storage.com / password');
        $this->command->info('Tenant Staff: staff@demo-storage.com / password');
    }
}

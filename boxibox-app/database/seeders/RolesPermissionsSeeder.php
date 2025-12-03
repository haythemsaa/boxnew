<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // Tenant Management
            'view_tenants',
            'create_tenants',
            'edit_tenants',
            'delete_tenants',

            // Site Management
            'view_sites',
            'create_sites',
            'edit_sites',
            'delete_sites',

            // Box Management
            'view_boxes',
            'create_boxes',
            'edit_boxes',
            'delete_boxes',

            // Customer Management
            'view_customers',
            'create_customers',
            'edit_customers',
            'delete_customers',

            // Lead Management
            'view_leads',
            'create_leads',
            'edit_leads',
            'delete_leads',

            // Contract Management
            'view_contracts',
            'create_contracts',
            'edit_contracts',
            'delete_contracts',
            'sign_contracts',

            // Invoice Management
            'view_invoices',
            'create_invoices',
            'edit_invoices',
            'delete_invoices',
            'send_invoices',

            // Payment Management
            'view_payments',
            'create_payments',
            'process_payments',
            'refund_payments',

            // Message Management
            'view_messages',
            'send_messages',
            'delete_messages',

            // Notification Management
            'view_notifications',
            'send_notifications',

            // Floor Plan Management
            'view_floor_plans',
            'create_floor_plans',
            'edit_floor_plans',
            'delete_floor_plans',

            // Pricing Rule Management
            'view_pricing_rules',
            'create_pricing_rules',
            'edit_pricing_rules',
            'delete_pricing_rules',

            // Subscription Management
            'view_subscriptions',
            'manage_subscriptions',

            // Settings
            'manage_settings',
            'view_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create Roles

        // Super Admin - Full platform access
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Tenant Admin - Full tenant access
        $tenantAdmin = Role::create(['name' => 'tenant_admin']);
        $tenantAdmin->givePermissionTo([
            'view_sites', 'create_sites', 'edit_sites', 'delete_sites',
            'view_boxes', 'create_boxes', 'edit_boxes', 'delete_boxes',
            'view_customers', 'create_customers', 'edit_customers', 'delete_customers',
            'view_leads', 'create_leads', 'edit_leads', 'delete_leads',
            'view_contracts', 'create_contracts', 'edit_contracts', 'delete_contracts', 'sign_contracts',
            'view_invoices', 'create_invoices', 'edit_invoices', 'delete_invoices', 'send_invoices',
            'view_payments', 'create_payments', 'process_payments', 'refund_payments',
            'view_messages', 'send_messages', 'delete_messages',
            'view_notifications', 'send_notifications',
            'view_floor_plans', 'create_floor_plans', 'edit_floor_plans', 'delete_floor_plans',
            'view_pricing_rules', 'create_pricing_rules', 'edit_pricing_rules', 'delete_pricing_rules',
            'manage_settings', 'view_reports',
        ]);

        // Tenant Staff - Limited tenant access
        $tenantStaff = Role::create(['name' => 'tenant_staff']);
        $tenantStaff->givePermissionTo([
            'view_sites', 'view_boxes', 'edit_boxes',
            'view_customers', 'create_customers', 'edit_customers',
            'view_leads', 'create_leads', 'edit_leads',
            'view_contracts', 'create_contracts', 'edit_contracts',
            'view_invoices', 'create_invoices',
            'view_payments', 'create_payments',
            'view_messages', 'send_messages',
            'view_floor_plans',
        ]);

        // Client - Customer portal access
        $client = Role::create(['name' => 'client']);
        $client->givePermissionTo([
            'view_contracts',
            'view_invoices',
            'view_payments',
            'view_messages', 'send_messages',
            'view_notifications',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}

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
            'merge_customers',
            'export_customers',

            // Lead Management
            'view_leads',
            'create_leads',
            'edit_leads',
            'delete_leads',

            // Prospect Management
            'view_prospects',
            'create_prospects',
            'edit_prospects',
            'delete_prospects',
            'convert_prospects',

            // Contract Management
            'view_contracts',
            'create_contracts',
            'edit_contracts',
            'delete_contracts',
            'sign_contracts',
            'terminate_contracts',
            'renew_contracts',

            // Invoice Management
            'view_invoices',
            'create_invoices',
            'edit_invoices',
            'delete_invoices',
            'send_invoices',
            'update_invoices',

            // Payment Management
            'view_payments',
            'create_payments',
            'process_payments',
            'refund_payments',

            // Message Management
            'view_messages',
            'send_messages',
            'delete_messages',
            'send_bulk_messages',

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

            // User Management (Tenant)
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_user_roles',
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
            'view_customers', 'create_customers', 'edit_customers', 'delete_customers', 'merge_customers', 'export_customers',
            'view_leads', 'create_leads', 'edit_leads', 'delete_leads',
            'view_prospects', 'create_prospects', 'edit_prospects', 'delete_prospects', 'convert_prospects',
            'view_contracts', 'create_contracts', 'edit_contracts', 'delete_contracts', 'sign_contracts', 'terminate_contracts', 'renew_contracts',
            'view_invoices', 'create_invoices', 'edit_invoices', 'delete_invoices', 'send_invoices', 'update_invoices',
            'view_payments', 'create_payments', 'process_payments', 'refund_payments',
            'view_messages', 'send_messages', 'delete_messages', 'send_bulk_messages',
            'view_notifications', 'send_notifications',
            'view_floor_plans', 'create_floor_plans', 'edit_floor_plans', 'delete_floor_plans',
            'view_pricing_rules', 'create_pricing_rules', 'edit_pricing_rules', 'delete_pricing_rules',
            'manage_settings', 'view_reports',
        ]);

        // Tenant Manager - Management access without settings
        $tenantManager = Role::create(['name' => 'tenant_manager']);
        $tenantManager->givePermissionTo([
            'view_sites', 'edit_sites',
            'view_boxes', 'create_boxes', 'edit_boxes',
            'view_customers', 'create_customers', 'edit_customers', 'merge_customers', 'export_customers',
            'view_leads', 'create_leads', 'edit_leads', 'delete_leads',
            'view_prospects', 'create_prospects', 'edit_prospects', 'delete_prospects', 'convert_prospects',
            'view_contracts', 'create_contracts', 'edit_contracts', 'sign_contracts', 'terminate_contracts', 'renew_contracts',
            'view_invoices', 'create_invoices', 'edit_invoices', 'send_invoices',
            'view_payments', 'create_payments', 'process_payments',
            'view_messages', 'send_messages', 'send_bulk_messages',
            'view_notifications', 'send_notifications',
            'view_floor_plans', 'edit_floor_plans',
            'view_pricing_rules',
            'view_reports',
        ]);

        // Tenant Staff - Limited tenant access
        $tenantStaff = Role::create(['name' => 'tenant_staff']);
        $tenantStaff->givePermissionTo([
            'view_sites', 'view_boxes', 'edit_boxes',
            'view_customers', 'create_customers', 'edit_customers',
            'view_leads', 'create_leads', 'edit_leads',
            'view_prospects', 'create_prospects', 'edit_prospects',
            'view_contracts', 'create_contracts', 'edit_contracts',
            'view_invoices', 'create_invoices',
            'view_payments', 'create_payments',
            'view_messages', 'send_messages',
            'view_floor_plans',
        ]);

        // Tenant Accountant - Financial access
        $tenantAccountant = Role::create(['name' => 'tenant_accountant']);
        $tenantAccountant->givePermissionTo([
            'view_sites',
            'view_boxes',
            'view_customers', 'export_customers',
            'view_contracts',
            'view_invoices', 'create_invoices', 'edit_invoices', 'send_invoices', 'update_invoices',
            'view_payments', 'create_payments', 'process_payments', 'refund_payments',
            'view_reports',
        ]);

        // Tenant Viewer - Read-only access
        $tenantViewer = Role::create(['name' => 'tenant_viewer']);
        $tenantViewer->givePermissionTo([
            'view_sites',
            'view_boxes',
            'view_customers',
            'view_leads',
            'view_prospects',
            'view_contracts',
            'view_invoices',
            'view_payments',
            'view_messages',
            'view_notifications',
            'view_floor_plans',
            'view_pricing_rules',
            'view_reports',
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

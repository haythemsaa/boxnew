<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance optimization indexes
 * These indexes are critical for production performance on tables with high query volume.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Contracts - Frequently filtered by dates and status combinations
        Schema::table('contracts', function (Blueprint $table) {
            $table->index(['tenant_id', 'status', 'start_date'], 'contracts_tenant_status_start');
            $table->index(['tenant_id', 'status', 'end_date'], 'contracts_tenant_status_end');
            $table->index(['tenant_id', 'site_id', 'status'], 'contracts_tenant_site_status');
            $table->index('start_date');
            $table->index('end_date');
            $table->index(['customer_id', 'status']);
            $table->index('auto_renew');
        });

        // Invoices - Critical for financial queries and reporting
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['tenant_id', 'status', 'due_date'], 'invoices_tenant_status_due');
            $table->index(['tenant_id', 'invoice_date'], 'invoices_tenant_date');
            $table->index(['contract_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('invoice_date');
            $table->index('paid_at');
        });

        // Payments - Transaction lookups and reconciliation
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['tenant_id', 'paid_at'], 'payments_tenant_date');
            $table->index(['invoice_id', 'status']);
            $table->index('paid_at');
            $table->index('status');
            $table->index('payment_method');
        });

        // Boxes - Availability searches are critical for booking
        Schema::table('boxes', function (Blueprint $table) {
            $table->index(['site_id', 'status'], 'boxes_site_status');
            $table->index(['tenant_id', 'status', 'current_price'], 'boxes_tenant_status_price');
            $table->index('current_price');
            $table->index('volume');
        });

        // Customers - Lookups by various fields
        Schema::table('customers', function (Blueprint $table) {
            $table->index(['tenant_id', 'created_at'], 'customers_tenant_created');
            $table->index('phone');
            $table->index('last_name');
        });

        // Bookings - Status tracking and conversions
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['tenant_id', 'created_at'], 'bookings_tenant_created');
            $table->index('created_at');
            $table->index('start_date');
        });

        // Leads - CRM and sales pipeline
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->index(['tenant_id', 'status'], 'leads_tenant_status');
                $table->index(['tenant_id', 'created_at'], 'leads_tenant_created');
                $table->index('status');
                $table->index('source');
            });
        }

        // Prospects - Follow-up and conversion tracking
        if (Schema::hasTable('prospects')) {
            Schema::table('prospects', function (Blueprint $table) {
                $table->index(['tenant_id', 'status'], 'prospects_tenant_status');
                $table->index(['tenant_id', 'created_at'], 'prospects_tenant_created');
                $table->index('status');
            });
        }

        // Payment reminders - Due date queries
        if (Schema::hasTable('payment_reminders')) {
            Schema::table('payment_reminders', function (Blueprint $table) {
                $table->index(['tenant_id', 'status'], 'reminders_tenant_status');
                $table->index('due_date');
                $table->index('status');
            });
        }

        // Activity logs - Audit queries
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->index(['tenant_id', 'created_at'], 'activity_tenant_created');
                $table->index(['subject_type', 'subject_id'], 'activity_subject');
            });
        }

        // Notifications - Unread lookups
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read');
            });
        }

        // Support tickets - Ticket management
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->index(['tenant_id', 'status'], 'tickets_tenant_status');
                $table->index('status');
                $table->index('priority');
            });
        }

        // Sites - Active sites lookup
        Schema::table('sites', function (Blueprint $table) {
            $table->index(['tenant_id', 'is_active'], 'sites_tenant_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Contracts
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('contracts_tenant_status_start');
            $table->dropIndex('contracts_tenant_status_end');
            $table->dropIndex('contracts_tenant_site_status');
            $table->dropIndex(['start_date']);
            $table->dropIndex(['end_date']);
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['auto_renew']);
        });

        // Invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_tenant_status_due');
            $table->dropIndex('invoices_tenant_date');
            $table->dropIndex(['contract_id', 'status']);
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['invoice_date']);
            $table->dropIndex(['paid_at']);
        });

        // Payments
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_tenant_date');
            $table->dropIndex(['invoice_id', 'status']);
            $table->dropIndex(['paid_at']);
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_method']);
        });

        // Boxes
        Schema::table('boxes', function (Blueprint $table) {
            $table->dropIndex('boxes_site_status');
            $table->dropIndex('boxes_tenant_status_price');
            $table->dropIndex(['current_price']);
            $table->dropIndex(['volume']);
        });

        // Customers
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_tenant_created');
            $table->dropIndex(['phone']);
            $table->dropIndex(['last_name']);
        });

        // Bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_tenant_created');
            $table->dropIndex(['created_at']);
            $table->dropIndex(['start_date']);
        });

        // Leads
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropIndex('leads_tenant_status');
                $table->dropIndex('leads_tenant_created');
                $table->dropIndex(['status']);
                $table->dropIndex(['source']);
            });
        }

        // Prospects
        if (Schema::hasTable('prospects')) {
            Schema::table('prospects', function (Blueprint $table) {
                $table->dropIndex('prospects_tenant_status');
                $table->dropIndex('prospects_tenant_created');
                $table->dropIndex(['status']);
            });
        }

        // Payment reminders
        if (Schema::hasTable('payment_reminders')) {
            Schema::table('payment_reminders', function (Blueprint $table) {
                $table->dropIndex('reminders_tenant_status');
                $table->dropIndex(['due_date']);
                $table->dropIndex(['status']);
            });
        }

        // Activity logs
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropIndex('activity_tenant_created');
                $table->dropIndex('activity_subject');
            });
        }

        // Notifications
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndex('notifications_notifiable_read');
            });
        }

        // Support tickets
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->dropIndex('tickets_tenant_status');
                $table->dropIndex(['status']);
                $table->dropIndex(['priority']);
            });
        }

        // Sites
        Schema::table('sites', function (Blueprint $table) {
            $table->dropIndex('sites_tenant_active');
        });
    }
};

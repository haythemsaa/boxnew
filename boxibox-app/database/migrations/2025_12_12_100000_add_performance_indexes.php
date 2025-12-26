<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Performance optimization indexes
 * These indexes are critical for production performance on tables with high query volume.
 */
return new class extends Migration
{
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Contracts - Frequently filtered by dates and status combinations
        Schema::table('contracts', function (Blueprint $table) {
            if (!$this->indexExists('contracts', 'contracts_tenant_status_start')) {
                $table->index(['tenant_id', 'status', 'start_date'], 'contracts_tenant_status_start');
            }
            if (!$this->indexExists('contracts', 'contracts_tenant_status_end')) {
                $table->index(['tenant_id', 'status', 'end_date'], 'contracts_tenant_status_end');
            }
            if (!$this->indexExists('contracts', 'contracts_tenant_site_status')) {
                $table->index(['tenant_id', 'site_id', 'status'], 'contracts_tenant_site_status');
            }
            if (!$this->indexExists('contracts', 'contracts_start_date_index')) {
                $table->index('start_date');
            }
            if (!$this->indexExists('contracts', 'contracts_end_date_index')) {
                $table->index('end_date');
            }
            if (!$this->indexExists('contracts', 'contracts_customer_id_status_index')) {
                $table->index(['customer_id', 'status']);
            }
            if (!$this->indexExists('contracts', 'contracts_auto_renew_index')) {
                $table->index('auto_renew');
            }
        });

        // Invoices - Critical for financial queries and reporting
        Schema::table('invoices', function (Blueprint $table) {
            if (!$this->indexExists('invoices', 'invoices_tenant_status_due')) {
                $table->index(['tenant_id', 'status', 'due_date'], 'invoices_tenant_status_due');
            }
            if (!$this->indexExists('invoices', 'invoices_tenant_date')) {
                $table->index(['tenant_id', 'invoice_date'], 'invoices_tenant_date');
            }
            if (!$this->indexExists('invoices', 'invoices_contract_id_status_index')) {
                $table->index(['contract_id', 'status']);
            }
            if (!$this->indexExists('invoices', 'invoices_customer_id_status_index')) {
                $table->index(['customer_id', 'status']);
            }
            if (!$this->indexExists('invoices', 'invoices_invoice_date_index')) {
                $table->index('invoice_date');
            }
            if (!$this->indexExists('invoices', 'invoices_paid_at_index')) {
                $table->index('paid_at');
            }
        });

        // Payments - Transaction lookups and reconciliation
        Schema::table('payments', function (Blueprint $table) {
            if (!$this->indexExists('payments', 'payments_tenant_date')) {
                $table->index(['tenant_id', 'paid_at'], 'payments_tenant_date');
            }
            if (!$this->indexExists('payments', 'payments_invoice_id_status_index')) {
                $table->index(['invoice_id', 'status']);
            }
            if (!$this->indexExists('payments', 'payments_paid_at_index')) {
                $table->index('paid_at');
            }
            if (!$this->indexExists('payments', 'payments_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('payments', 'payments_payment_method_index')) {
                $table->index('payment_method');
            }
        });

        // Boxes - Availability searches are critical for booking
        Schema::table('boxes', function (Blueprint $table) {
            if (!$this->indexExists('boxes', 'boxes_site_status')) {
                $table->index(['site_id', 'status'], 'boxes_site_status');
            }
            if (!$this->indexExists('boxes', 'boxes_tenant_status_price')) {
                $table->index(['tenant_id', 'status', 'current_price'], 'boxes_tenant_status_price');
            }
            if (!$this->indexExists('boxes', 'boxes_current_price_index')) {
                $table->index('current_price');
            }
            if (!$this->indexExists('boxes', 'boxes_volume_index')) {
                $table->index('volume');
            }
        });

        // Customers - Lookups by various fields
        Schema::table('customers', function (Blueprint $table) {
            if (!$this->indexExists('customers', 'customers_tenant_created')) {
                $table->index(['tenant_id', 'created_at'], 'customers_tenant_created');
            }
            if (!$this->indexExists('customers', 'customers_phone_index')) {
                $table->index('phone');
            }
            if (!$this->indexExists('customers', 'customers_last_name_index')) {
                $table->index('last_name');
            }
        });

        // Bookings - Status tracking and conversions
        Schema::table('bookings', function (Blueprint $table) {
            if (!$this->indexExists('bookings', 'bookings_tenant_created')) {
                $table->index(['tenant_id', 'created_at'], 'bookings_tenant_created');
            }
            if (!$this->indexExists('bookings', 'bookings_created_at_index')) {
                $table->index('created_at');
            }
            if (!$this->indexExists('bookings', 'bookings_start_date_index')) {
                $table->index('start_date');
            }
        });

        // Leads - CRM and sales pipeline
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                if (!$this->indexExists('leads', 'leads_tenant_status')) {
                    $table->index(['tenant_id', 'status'], 'leads_tenant_status');
                }
                if (!$this->indexExists('leads', 'leads_tenant_created')) {
                    $table->index(['tenant_id', 'created_at'], 'leads_tenant_created');
                }
                if (!$this->indexExists('leads', 'leads_status_index')) {
                    $table->index('status');
                }
                if (!$this->indexExists('leads', 'leads_source_index')) {
                    $table->index('source');
                }
            });
        }

        // Prospects - Follow-up and conversion tracking
        if (Schema::hasTable('prospects')) {
            Schema::table('prospects', function (Blueprint $table) {
                if (!$this->indexExists('prospects', 'prospects_tenant_status')) {
                    $table->index(['tenant_id', 'status'], 'prospects_tenant_status');
                }
                if (!$this->indexExists('prospects', 'prospects_tenant_created')) {
                    $table->index(['tenant_id', 'created_at'], 'prospects_tenant_created');
                }
                if (!$this->indexExists('prospects', 'prospects_status_index')) {
                    $table->index('status');
                }
            });
        }

        // Payment reminders - Due date queries
        if (Schema::hasTable('payment_reminders')) {
            Schema::table('payment_reminders', function (Blueprint $table) {
                if (Schema::hasColumn('payment_reminders', 'tenant_id') && Schema::hasColumn('payment_reminders', 'status')) {
                    if (!$this->indexExists('payment_reminders', 'reminders_tenant_status')) {
                        $table->index(['tenant_id', 'status'], 'reminders_tenant_status');
                    }
                }
                if (Schema::hasColumn('payment_reminders', 'due_date')) {
                    if (!$this->indexExists('payment_reminders', 'payment_reminders_due_date_index')) {
                        $table->index('due_date');
                    }
                }
                if (Schema::hasColumn('payment_reminders', 'status')) {
                    if (!$this->indexExists('payment_reminders', 'payment_reminders_status_index')) {
                        $table->index('status');
                    }
                }
            });
        }

        // Activity logs - Audit queries
        if (Schema::hasTable('activity_logs')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                if (Schema::hasColumn('activity_logs', 'tenant_id') && Schema::hasColumn('activity_logs', 'created_at')) {
                    if (!$this->indexExists('activity_logs', 'activity_tenant_created')) {
                        $table->index(['tenant_id', 'created_at'], 'activity_tenant_created');
                    }
                }
                if (Schema::hasColumn('activity_logs', 'subject_type') && Schema::hasColumn('activity_logs', 'subject_id')) {
                    if (!$this->indexExists('activity_logs', 'activity_subject')) {
                        $table->index(['subject_type', 'subject_id'], 'activity_subject');
                    }
                }
            });
        }

        // Notifications - Unread lookups
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (Schema::hasColumn('notifications', 'notifiable_type') && Schema::hasColumn('notifications', 'notifiable_id') && Schema::hasColumn('notifications', 'read_at')) {
                    if (!$this->indexExists('notifications', 'notifications_notifiable_read')) {
                        $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read');
                    }
                }
            });
        }

        // Support tickets - Ticket management
        if (Schema::hasTable('support_tickets')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                if (Schema::hasColumn('support_tickets', 'tenant_id') && Schema::hasColumn('support_tickets', 'status')) {
                    if (!$this->indexExists('support_tickets', 'tickets_tenant_status')) {
                        $table->index(['tenant_id', 'status'], 'tickets_tenant_status');
                    }
                }
                if (Schema::hasColumn('support_tickets', 'status')) {
                    if (!$this->indexExists('support_tickets', 'support_tickets_status_index')) {
                        $table->index('status');
                    }
                }
                if (Schema::hasColumn('support_tickets', 'priority')) {
                    if (!$this->indexExists('support_tickets', 'support_tickets_priority_index')) {
                        $table->index('priority');
                    }
                }
            });
        }

        // Sites - Active sites lookup
        Schema::table('sites', function (Blueprint $table) {
            if (!$this->indexExists('sites', 'sites_tenant_active')) {
                $table->index(['tenant_id', 'is_active'], 'sites_tenant_active');
            }
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

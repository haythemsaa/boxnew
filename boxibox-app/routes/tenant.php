<?php

/**
 * Tenant Routes - Separated for better organization
 *
 * This file is loaded via the RouteServiceProvider or bootstrap/app.php
 * All routes are prefixed with /tenant and named with tenant.
 *
 * Example usage in bootstrap/app.php:
 * ->withRouting(
 *     web: __DIR__.'/../routes/web.php',
 *     api: __DIR__.'/../routes/api.php',
 *     tenant: __DIR__.'/../routes/tenant.php',
 *     commands: __DIR__.'/../routes/console.php',
 *     health: '/up',
 * )
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\{
    DashboardController,
    SiteController,
    BoxController,
    CustomerController,
    ContractController,
    InvoiceController,
    PaymentController,
    AnalyticsController,
    PricingController,
    LeadController,
    AccessControlController,
    ProspectController,
    SignatureController,
    SepaMandateController,
    ReminderController,
    BulkInvoiceController,
    PlanController,
    BookingManagementController,
    MaintenanceController,
    OverdueController,
    ReportingController,
    InspectionController,
    LoyaltyController,
    StaffController,
    CalculatorController as TenantCalculatorController,
    ReviewController,
    GdprController,
    VideoCallController,
    UserController as TenantUserController,
    OnboardingController
};

/*
|--------------------------------------------------------------------------
| Tenant Routes Organization
|--------------------------------------------------------------------------
|
| This file would contain all tenant routes when the refactoring is complete.
| For now, routes remain in web.php but can be migrated here gradually.
|
| Route Groups:
| - Dashboard & General
| - Users & Onboarding
| - Sites & Boxes
| - Customers
| - Contracts & Signatures
| - Invoices & Payments
| - CRM (Leads, Prospects)
| - Analytics & AI
| - Operations (Maintenance, Inspections)
| - Integrations & Webhooks
| - Settings
|
*/

// This file is prepared for future route organization
// Routes can be migrated from web.php gradually

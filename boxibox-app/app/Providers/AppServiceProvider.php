<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Prospect;
use App\Policies\BookingPolicy;
use App\Policies\ContractPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LeadPolicy;
use App\Policies\MessagePolicy;
use App\Policies\ProspectPolicy;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Contract::class, ContractPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);
        Gate::policy(Prospect::class, ProspectPolicy::class);

        // Configure Scramble API documentation
        Scramble::configure()
            ->expose(
                ui: 'docs/api',
                document: 'docs/api.json'
            )
            ->afterOpenApiGenerated(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')
                );
            });

        // Allow access to API docs for all users in local/development environment
        Gate::define('viewApiDocs', function ($user = null) {
            return app()->environment(['local', 'development']) ||
                   ($user && $user->hasRole(['super-admin', 'admin']));
        });
    }
}

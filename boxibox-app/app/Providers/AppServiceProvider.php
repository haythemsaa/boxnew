<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Payment;
use App\Models\Prospect;
use App\Models\Site;
use App\Observers\DashboardCacheObserver;
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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();
        $this->registerObservers();

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

    /**
     * Register model observers for cache invalidation.
     */
    protected function registerObservers(): void
    {
        // Dashboard cache invalidation - automatically clear cache when models change
        $modelsToObserve = [
            Contract::class,
            Invoice::class,
            Payment::class,
            Customer::class,
            Box::class,
            Site::class,
        ];

        foreach ($modelsToObserve as $model) {
            $model::observe(DashboardCacheObserver::class);
        }
    }

    /**
     * Configure rate limiting for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Default API rate limiter - 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Stricter rate limit for authentication attempts - 5 per minute
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email') . '|' . $request->ip());
        });

        // Password reset - 3 per minute
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->input('email') . '|' . $request->ip());
        });

        // Public booking form - 10 per minute per IP
        RateLimiter::for('booking', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Webhook endpoints - Higher limit for integrations - 120 per minute
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        // Payment processing - 10 per minute (prevent brute force)
        RateLimiter::for('payments', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // File uploads - 30 per minute
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // SMS/Email sending - 20 per minute to prevent abuse
        RateLimiter::for('notifications', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // Export/Report generation - 5 per minute (heavy operations)
        RateLimiter::for('exports', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // Customer portal API - 30 per minute
        RateLimiter::for('portal', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Global rate limit for unauthenticated users - 30 per minute
        RateLimiter::for('global', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip());
        });
    }
}

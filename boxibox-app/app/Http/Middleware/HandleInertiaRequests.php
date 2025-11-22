<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            // Authentication
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                ] : null,
            ],

            // Current Tenant
            'tenant' => function () use ($request) {
                if ($request->user() && $request->user()->tenant_id) {
                    $tenant = \App\Models\Tenant::find($request->user()->tenant_id);
                    return $tenant ? [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'logo' => $tenant->logo,
                        'plan' => $tenant->plan,
                        'primary_color' => $tenant->primary_color,
                        'secondary_color' => $tenant->secondary_color,
                        'is_trial' => $tenant->is_trial,
                        'is_subscription_active' => $tenant->is_subscription_active,
                    ] : null;
                }
                return null;
            },

            // Flash Messages
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],

            // App Configuration
            'app' => [
                'name' => config('app.name'),
                'url' => config('app.url'),
                'locale' => app()->getLocale(),
            ],

            // Notifications count
            'notificationsCount' => function () use ($request) {
                if ($request->user()) {
                    return \App\Models\Notification::where('user_id', $request->user()->id)
                        ->unread()
                        ->count();
                }
                return 0;
            },

            // Unread messages count
            'messagesCount' => function () use ($request) {
                if ($request->user()) {
                    return \App\Models\Message::where('recipient_id', $request->user()->id)
                        ->unread()
                        ->count();
                }
                return 0;
            },
        ];
    }
}

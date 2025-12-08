<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales
     */
    protected array $supportedLocales = ['fr', 'en', 'nl', 'es'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for locale in session
        $locale = Session::get('locale');

        // If not in session, check cookie
        if (!$locale) {
            $locale = $request->cookie('locale');
        }

        // If not in cookie, check Accept-Language header
        if (!$locale) {
            $locale = $this->getPreferredLocale($request);
        }

        // Default to French if still not set or not supported
        if (!$locale || !in_array($locale, $this->supportedLocales)) {
            $locale = 'fr';
        }

        // Set the locale
        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }

    /**
     * Get preferred locale from Accept-Language header
     */
    protected function getPreferredLocale(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (!$acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header
        $languages = explode(',', $acceptLanguage);

        foreach ($languages as $language) {
            $lang = strtolower(substr(trim(explode(';', $language)[0]), 0, 2));

            if (in_array($lang, $this->supportedLocales)) {
                return $lang;
            }
        }

        return null;
    }
}

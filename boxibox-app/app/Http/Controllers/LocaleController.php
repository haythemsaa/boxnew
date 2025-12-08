<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Supported locales
     */
    protected array $supportedLocales = ['fr', 'en', 'nl', 'es'];

    /**
     * Change the application locale
     */
    public function change(Request $request, string $locale)
    {
        if (!in_array($locale, $this->supportedLocales)) {
            return back()->with('error', 'Langue non supportée');
        }

        // Set locale in session
        Session::put('locale', $locale);
        App::setLocale($locale);

        // Set cookie (30 days)
        return back()
            ->cookie('locale', $locale, 60 * 24 * 30)
            ->with('success', 'Langue changée avec succès');
    }

    /**
     * Get translations for a locale (for JavaScript)
     */
    public function translations(string $locale)
    {
        if (!in_array($locale, $this->supportedLocales)) {
            $locale = 'fr';
        }

        $path = base_path("lang/{$locale}.json");

        if (!File::exists($path)) {
            return response()->json([]);
        }

        $translations = json_decode(File::get($path), true);

        return response()
            ->json($translations)
            ->header('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
    }

    /**
     * Get available locales
     */
    public function available()
    {
        return response()->json([
            ['code' => 'fr', 'name' => 'Français', 'flag' => '🇫🇷'],
            ['code' => 'en', 'name' => 'English', 'flag' => '🇬🇧'],
            ['code' => 'nl', 'name' => 'Nederlands', 'flag' => '🇳🇱'],
            ['code' => 'es', 'name' => 'Español', 'flag' => '🇪🇸'],
        ]);
    }
}

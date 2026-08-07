<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    protected static ?array $cachedTranslations = null;

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user && !$user->is_active) {
            \Illuminate\Support\Facades\Auth::guard('web')->logout();
            $user = null;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'locale' => app()->getLocale(),
            'translations' => $this->getTranslations(),
        ];
    }

    /**
     * Get translations for the current locale.
     */
    protected function getTranslations(): array
    {
        $locale = app()->getLocale();
        if (isset(self::$cachedTranslations[$locale])) {
            return self::$cachedTranslations[$locale];
        }

        $file = base_path("lang/{$locale}.json");

        if (file_exists($file)) {
            return self::$cachedTranslations[$locale] = json_decode(file_get_contents($file), true) ?? [];
        }

        return self::$cachedTranslations[$locale] = [];
    }
}

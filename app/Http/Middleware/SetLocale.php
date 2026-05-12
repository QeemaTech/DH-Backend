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
    protected array $supportedLocales = ['en', 'ar'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        // For API requests, prioritize Accept-Language header.
        $localeSource = 'default';

        if ($request->is('api/*')) {
            [$locale, $localeSource] = $this->resolveLocaleFromApiRequest($request);
        }

        // For web/dashboard requests, keep session-based behavior.
        if (! $locale && Session::has('locale')) {
            $sessionLocale = Session::get('locale');
            if (in_array($sessionLocale, $this->supportedLocales, true)) {
                $locale = $sessionLocale;
            }
        }

        // Fallback to app locale.
        $locale = $locale ?: config('app.locale', 'en');
        App::setLocale($locale);

        // Keep session in sync for non-API requests when locale is valid.
        if (! $request->is('api/*') && in_array($locale, $this->supportedLocales, true)) {
            Session::put('locale', $locale);
        }

        $response = $next($request);

        if ($request->is('api/*')) {
            $response->headers->set('X-App-Locale', App::getLocale());
            $response->headers->set('X-Locale-Source', $localeSource);
            $response->headers->set('X-Received-Accept-Language', (string) $request->header('Accept-Language', ''));
        }

        return $response;
    }

    protected function resolveLocaleFromApiRequest(Request $request): array
    {
        // 1) Explicit query parameter
        $queryLocale = strtolower((string) $request->query('lang', ''));
        if (in_array($queryLocale, $this->supportedLocales, true)) {
            return [$queryLocale, 'query:lang'];
        }

        // 2) Custom explicit header
        $explicitHeader = strtolower((string) $request->header('X-Locale', ''));
        if (in_array($explicitHeader, $this->supportedLocales, true)) {
            return [$explicitHeader, 'header:x-locale'];
        }

        // 3) Standard Accept-Language parser
        $fromAcceptLanguage = $this->resolveLocaleFromHeader($request->header('Accept-Language'));
        if ($fromAcceptLanguage) {
            return [$fromAcceptLanguage, 'header:accept-language'];
        }

        // 4) Laravel preferred language resolver (extra fallback)
        $preferred = $request->getPreferredLanguage($this->supportedLocales);
        if ($preferred && in_array($preferred, $this->supportedLocales, true)) {
            return [$preferred, 'preferred-language'];
        }

        return [null, 'default'];
    }

    protected function resolveLocaleFromHeader(?string $acceptLanguage): ?string
    {
        if (! $acceptLanguage) {
            return null;
        }

        $candidates = array_map('trim', explode(',', $acceptLanguage));

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            // Handle values like: ar, ar-EG, ar;q=0.9
            $langPart = strtolower(trim(explode(';', $candidate)[0]));
            $primary = explode('-', $langPart)[0] ?? $langPart;

            if (in_array($primary, $this->supportedLocales, true)) {
                return $primary;
            }
        }

        return null;
    }
}

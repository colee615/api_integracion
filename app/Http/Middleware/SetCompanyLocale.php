<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->attributes->get('currentCompany')
            ?? $request->attributes->get('companyPortalUser')?->company;

        $locale = self::resolveLocale(
            $request,
            $company?->locale
        );

        App::setLocale($locale);

        return $next($request);
    }

    public static function resolveLocale(Request $request, ?string $companyLocale): string
    {
        $supported = ['es', 'en'];
        $header = strtolower((string) $request->header('Accept-Language', ''));

        foreach ($supported as $locale) {
            if (str_contains($header, $locale)) {
                return $locale;
            }
        }

        return is_string($companyLocale) && in_array($companyLocale, $supported, true)
            ? $companyLocale
            : 'es';
    }
}

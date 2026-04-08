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

        $locale = $company?->locale;

        App::setLocale(is_string($locale) && $locale !== '' ? $locale : 'es');

        return $next($request);
    }
}

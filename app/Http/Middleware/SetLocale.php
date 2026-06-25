<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** @var list<string> */
    public const SUPPORTED = ['id', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale', 'id'));

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'id';
        }

        App::setLocale($locale);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

/**
 * Class ApiAuthentication
 * @package App\Http\Middleware
 */
class ApiAuthentication
{
    const HEADER = 'Authentication';
    const HEADER_PREFIX = 'Token ';

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $authentication = $request->header(self::HEADER);
        if (!Str::startsWith($authentication, self::HEADER_PREFIX)) {
            abort(401, 'Please provide a valid client key.');
            return;
        }

        $authentication = Str::substr($authentication, Str::length(self::HEADER_PREFIX));

        if ($authentication !== config('catlab-discover.clientKey')) {
            abort(401, 'Please provide a valid client key.');
            return;
        }

        return $next($request);
    }
}

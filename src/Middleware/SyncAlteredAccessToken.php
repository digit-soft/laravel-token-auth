<?php

namespace DigitSoft\LaravelTokenAuth\Middleware;

use Illuminate\Support\Facades\Auth;
use DigitSoft\LaravelTokenAuth\Contracts\TokenGuard;
use DigitSoft\LaravelTokenAuth\Contracts\AlteredByAccessToken;

class SyncAlteredAccessToken
{
    /**
     * Handle request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, \Closure $next)
    {
        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);
        $guard = Auth::guard();
        if ($guard instanceof TokenGuard && ($user = $guard->user()) instanceof AlteredByAccessToken) {
            $user->syncAccessTokenData();
        }

        return $response;
    }
}

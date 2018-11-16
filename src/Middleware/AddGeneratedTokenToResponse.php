<?php

namespace DigitSoft\LaravelTokenAuth\Middleware;

use DigitSoft\LaravelTokenAuth\Contracts\TokenGuard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Class AddGeneratedTokenToResponse.
 * This middleware adds token id to specified header if token was generated during request.
 * E.g. guest token for session data or other cases.
 * @package App\Http\Middleware
 * @codeCoverageIgnore
 */
class AddGeneratedTokenToResponse
{
    /**
     * Handle request.
     *
     * @param Request $request
     * @param \Closure $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, \Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);
        if (($token = $this->getTokenGenerated()) === null || !method_exists($response, 'header')) {
            return $response;
        }
        $response->header(config('auth-token.response_token_header'), $token->token);
        return $response;
    }

    /**
     * Get token, that was generated for request.
     * This function is getting token from auth guard and (if last exists) compares it with token given by user in request.
     * If this tokens are not equals then returns new one (generated during request).
     *
     * @return \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|null
     */
    protected function getTokenGenerated()
    {
        $guard = Auth::guard();
        if (!$guard instanceof TokenGuard || ($token = $guard->token()) === null) {
            return null;
        }
        return $token->saved() && $token->token !== $guard->getTokenForRequest() ? $token : null;
    }
}
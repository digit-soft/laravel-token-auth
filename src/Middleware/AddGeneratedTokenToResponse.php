<?php

namespace DigitSoft\LaravelTokenAuth\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use DigitSoft\LaravelTokenAuth\Contracts\TokenGuard;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken;

/**
 * Class AddGeneratedTokenToResponse.
 *
 * This middleware adds token id to specified header if token was generated during request.
 * E.g. guest token for session data or other cases.
 *
 * @codeCoverageIgnore
 */
class AddGeneratedTokenToResponse
{
    /**
     * Handle request.
     *
     * @param  Request  $request
     * @param  \Closure $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, \Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);
        if (($token = $this->getTokenGenerated()) === null || ! method_exists($response, 'header')) {
            return $response;
        }
        $response->header(config('auth-token.response_token_header'), $token->token);

        return $response;
    }

    /**
     * Get token, that was generated for request.
     * This function is getting token from auth guard and (if last exists) compares it with token given by user in request.
     * If these tokens are not equals then returns new one (generated during request).
     *
     * @return \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|null
     */
    protected function getTokenGenerated(): ?AccessToken
    {
        $guard = Auth::guard();
        if (! $guard instanceof TokenGuard || ($token = $guard->token()) === null) {
            return null;
        }

        return $token->wasSaved() && $token->token !== $guard->getTokenForRequest() ? $token : null;
    }
}

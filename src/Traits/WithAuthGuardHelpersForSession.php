<?php

namespace DigitSoft\LaravelTokenAuth\Traits;

use DigitSoft\LaravelTokenAuth\Contracts\TokenGuard;
use \DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;

trait WithAuthGuardHelpersForSession
{
    /**
     * Get current token to save data.
     *
     * @return \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|null
     */
    protected function getToken(): ?AccessTokenContract
    {
        $guard = \Auth::guard();
        if (! $guard instanceof TokenGuard || ($token = $this->getTokenOrCreate($guard)) === null) {
            return null;
        }

        return $token;
    }

    /**
     * Get user token or create new for guest.
     *
     * @param  \DigitSoft\LaravelTokenAuth\Contracts\TokenGuard $guard
     * @return \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|null
     */
    protected function getTokenOrCreate(TokenGuard $guard): ?AccessTokenContract
    {
        if (($token = $guard->token()) === null && $this->shouldCreateGuestTokenWhenMissing()) {
            $token = \TokenCached::createForGuest();
            $guard->setToken($token);
        }

        return $token;
    }

    /**
     * Determines whether token should be created for a guest user.
     *
     * @return bool
     */
    protected function shouldCreateGuestTokenWhenMissing(): bool
    {
        return config('auth-token.session_token_autocreate', false);
    }
}

<?php

namespace DigitSoft\LaravelTokenAuth\Contracts;

use DigitSoft\LaravelTokenAuth\Eloquent\HasTokens;

/**
 * Interface Guard
 * @package DigitSoft\LaravelTokenAuth\Contracts
 * @method void fake() Used for testing in stateless environment
 * @method void reset() Reset guard state
 */
interface TokenGuard extends \Illuminate\Contracts\Auth\Guard
{
    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function once(array $credentials = []): bool;

    /**
     * Log a user into the application without sessions or cookies externally checked and got.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|HasTokens $user
     * @return bool
     */
    public function onceExternal($user): bool;

    /**
     * Get access token object for current request
     * @return \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|null
     */
    public function token(): ?AccessToken;

    /**
     * Set token object
     * @param \DigitSoft\LaravelTokenAuth\Contracts\AccessToken $token
     */
    public function setToken(AccessToken $token): void;

    /**
     * Get the token for the current request.
     *
     * @return string|null
     */
    public function getTokenForRequest(): ?string;
}

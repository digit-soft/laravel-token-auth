<?php

namespace DigitSoft\LaravelTokenAuth\Contracts;

/**
 * Interface Guard
 * @package DigitSoft\LaravelTokenAuth\Contracts
 */
interface TokenGuard extends \Illuminate\Contracts\Auth\Guard
{
    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function once(array $credentials = []);

    /**
     * Get access token object for current request
     * @return AccessToken|null
     */
    public function token();

    /**
     * Set token object
     * @param AccessToken $token
     */
    public function setToken(AccessToken $token);

    /**
     * Get the token for the current request.
     *
     * @return string
     */
    public function getTokenForRequest();
}
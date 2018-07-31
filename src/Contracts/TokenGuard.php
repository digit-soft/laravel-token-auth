<?php

namespace DigitSoft\LaravelTokenAuth\Contracts;

/**
 * Interface Guard
 * @package DigitSoft\LaravelTokenAuth\Contracts
 */
interface TokenGuard extends \Illuminate\Contracts\Auth\Guard
{
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
}
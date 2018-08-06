<?php

namespace DigitSoft\LaravelTokenAuth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class AccessToken facade
 * @package DigitSoft\LaravelTokenAuth\Facades
 * @method static \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|null getFirstFor(\Illuminate\Contracts\Auth\Authenticatable $user, $client_id = null, \DigitSoft\LaravelTokenAuth\Contracts\Storage $storage = null)
 * @method static \DigitSoft\LaravelTokenAuth\Contracts\AccessToken createFor(\Illuminate\Contracts\Auth\Authenticatable $user, $client_id = null, $autoTtl = true)
 * @method static \DigitSoft\LaravelTokenAuth\Contracts\AccessToken createForGuest($client_id = null, $autoTtl = true)
 * @method static \DigitSoft\LaravelTokenAuth\Contracts\AccessToken createFromData($data = [], $fromStorage = false)
 * @method static string getDefaultClientId()
 * @method static string getClientIdFromRequest(\Illuminate\Http\Request $request)
 * @codeCoverageIgnore
 * @see \DigitSoft\LaravelTokenAuth\AccessTokenHelper
 */
class TokenCached extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'auth-token';
    }
}
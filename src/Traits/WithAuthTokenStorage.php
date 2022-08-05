<?php

namespace DigitSoft\LaravelTokenAuth\Traits;

use DigitSoft\LaravelTokenAuth\Contracts\Storage;

trait WithAuthTokenStorage
{
    /**
     * Storage instance.
     *
     * @var \DigitSoft\LaravelTokenAuth\Contracts\Storage|null
     */
    public static ?Storage $_authTokenStorageInstance = null;

    /**
     * Get tokens storage instance.
     *
     * @return \DigitSoft\LaravelTokenAuth\Contracts\Storage|\DigitSoft\LaravelTokenAuth\Storage\Redis
     */
    public static function tokenStorage(): Storage
    {
        return static::$_authTokenStorageInstance ?? static::$_authTokenStorageInstance = app('auth-token.storage');
    }
}

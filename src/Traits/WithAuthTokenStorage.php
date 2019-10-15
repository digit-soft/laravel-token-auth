<?php

namespace DigitSoft\LaravelTokenAuth\Traits;

trait WithAuthTokenStorage
{
    /**
     * Storage instance.
     *
     * @var \DigitSoft\LaravelTokenAuth\Contracts\Storage
     */
    public static $_authTokenStorageInstance;

    /**
     * Get tokens storage instance.
     *
     * @return \DigitSoft\LaravelTokenAuth\Storage\Redis|\Illuminate\Contracts\Foundation\Application|mixed
     */
    public static function tokenStorage()
    {
        return WithAuthTokenStorage::$_authTokenStorageInstance ?? WithAuthTokenStorage::$_authTokenStorageInstance = app('auth-token.storage');
    }
}

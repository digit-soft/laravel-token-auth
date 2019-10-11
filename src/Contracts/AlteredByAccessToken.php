<?php

namespace DigitSoft\LaravelTokenAuth\Contracts;

/**
 * Interface AlteredByAccessToken.
 */
interface AlteredByAccessToken
{
    /**
     * Set access token for further data load.
     *
     * @param  \DigitSoft\LaravelTokenAuth\Contracts\AccessToken $accessToken
     */
    public function setAccessToken(AccessToken $accessToken);
}

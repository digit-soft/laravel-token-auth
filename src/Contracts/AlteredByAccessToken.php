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
    public function setAccessToken(AccessToken $accessToken): void;

    /**
     * Get access token.
     *
     * @return \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|null
     */
    public function getAccessToken(): ?AccessToken;

    /**
     * Sync data from model into token.
     */
    public function syncAccessTokenData(): void;
}

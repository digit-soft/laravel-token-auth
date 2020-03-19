<?php

namespace DigitSoft\LaravelTokenAuth\Eloquent;

use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Facades\TokenCached;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;

trait HasTokens
{
    /**
     * Get user active access tokens.
     *
     * @return AccessTokenContract[]
     */
    public function getTokens()
    {
        $id = $this->getAuthIdentifier();

        return $this->getTokensStorage()->getUserTokens($id, true);
    }

    /**
     * Get first active token.
     *
     * @param  string|null $client_id
     * @return AccessTokenContract
     */
    public function getToken($client_id = null)
    {
        $client_id = $client_id ?? $this->getClientIdFromRequest();
        $token = TokenCached::getFirstFor($this, $client_id);
        $token = $token ?? $this->createToken($client_id);

        return $token;
    }

    /**
     * Create new access token and save it to storage.
     *
     * @param  string|null $client_id
     * @param  int|null    $ttl
     * @return AccessTokenContract
     */
    public function createToken($client_id = null, $ttl = 0)
    {
        $token = TokenCached::createFor($this, $client_id);
        if ($ttl !== 0) {
            $token->setTtl($ttl);
        }
        $token->save();

        return $token;
    }

    /**
     * Get token storage instance.
     *
     * @return Storage
     */
    protected function getTokensStorage()
    {
        return app()->make(Storage::class);
    }

    /**
     * Get client ID from request.
     *
     * @return string
     */
    protected function getClientIdFromRequest()
    {
        return TokenCached::getClientIdFromRequest(app('request'));
    }
}

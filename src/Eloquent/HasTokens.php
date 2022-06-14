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
    public function getTokens(): array
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
    public function getToken(?string $client_id = null): AccessTokenContract
    {
        $client_id = $client_id ?? $this->getClientIdFromRequest();
        $token = TokenCached::getFirstFor($this, $client_id);

        return $token ?? $this->createToken($client_id);
    }

    /**
     * Create new access token and save it to storage.
     *
     * @param  string|null $client_id
     * @param  int|null    $ttl
     * @return AccessTokenContract
     */
    public function createToken(?string $client_id = null, ?int $ttl = null): AccessTokenContract
    {
        $token = TokenCached::createFor($this, $client_id);
        if ($ttl !== null) {
            $token->setTtl($ttl);
        }
        $token->save();

        return $token;
    }

    /**
     * Get token storage instance.
     *
     * @return \DigitSoft\LaravelTokenAuth\Contracts\Storage
     */
    protected function getTokensStorage(): Storage
    {
        return app()->make(Storage::class);
    }

    /**
     * Get client ID from request.
     *
     * @return string
     */
    protected function getClientIdFromRequest(): string
    {
        return TokenCached::getClientIdFromRequest(app('request'));
    }
}

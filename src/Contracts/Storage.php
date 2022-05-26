<?php

namespace DigitSoft\LaravelTokenAuth\Contracts;

/**
 * Interface Storage
 */
interface Storage
{
    /**
     * Get user token content
     *
     * @param  string $tokenId
     * @return AccessToken|null
     */
    public function getToken($tokenId): ?AccessToken;

    /**
     * Get user tokens content (multiple)
     *
     * @param  string[] $tokenIds
     * @return AccessToken[]
     */
    public function getTokens($tokenIds): array;

    /**
     * Set user token content
     *
     * @param  AccessToken $token
     * @return bool
     */
    public function setToken($token): bool;

    /**
     * Remove user token and its content
     *
     * @param  AccessToken $token
     * @return bool
     */
    public function removeToken($token): bool;

    /**
     * Check that token record exists in storage
     *
     * @param  AccessToken|string $token
     * @return bool
     */
    public function tokenExists($token): bool;

    /**
     * Get user => token assignment list
     *
     * @param  int  $user_id
     * @param  bool $load
     * @return AccessToken[]
     */
    public function getUserTokens($user_id, bool $load = false): array;

    /**
     * Set user => token assignment list
     *
     * @param  int           $user_id
     * @param  AccessToken[] $tokens
     */
    public function setUserTokens($user_id, array $tokens = []): void;
}

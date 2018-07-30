<?php

namespace DigitSoft\LaravelTokenAuth\Contracts;

/**
 * Interface Storage
 * @package DigitSoft\LaravelTokenAuth\Contracts
 */
interface Storage
{
    /**
     * Get user token content
     * @param string $tokenId
     * @return AccessToken|null
     */
    public function getToken($tokenId);

    /**
     * Get user tokens content (multiple)
     * @param string[] $tokenIds
     * @return AccessToken[]
     */
    public function getTokens($tokenIds);

    /**
     * Set user token content
     * @param AccessToken $token
     */
    public function setToken($token);

    /**
     * Remove user token and its content
     * @param AccessToken $token
     */
    public function removeToken($token);

    /**
     * Check that token record exists in storage
     * @param AccessToken|string $token
     * @return bool
     */
    public function tokenExists($token);

    /**
     * Get user => token assignment list
     * @param int  $user_id
     * @param bool $load
     * @return AccessToken[]
     */
    public function getUserTokens($user_id, $load = false);

    /**
     * Set user => token assignment list
     * @param int           $user_id
     * @param AccessToken[] $tokens
     */
    public function setUserTokens($user_id, $tokens = []);
}
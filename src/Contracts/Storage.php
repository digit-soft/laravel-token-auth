<?php

namespace DigitSoft\LaravelTokenAuth\Contracts;

use DigitSoft\LaravelTokenAuth\AccessToken;

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
     * Get user tokens list by ID
     * @param int  $userId
     * @param bool $load
     * @return array|AccessToken[]
     */
    public function getUserTokens($userId, $load = false);

    /**
     * Set user tokens list
     * @param int                    $userId
     * @param AccessToken[]|string[] $tokens
     */
    public function setUserTokens($userId, $tokens = []);

    /**
     * Add token to user list
     * @param AccessToken $token
     */
    public function addUserToken($token);
}
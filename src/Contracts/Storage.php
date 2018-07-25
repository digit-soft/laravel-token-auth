<?php

namespace DigitSoft\LaravelTokenAuth\Contracts;

interface Storage
{
    const TOKEN_KEY_USER_ID = '_user';
    const TOKEN_KEY_ROLES   = '_roles';
    const TOKEN_KEY_RIGHTS  = '_rights';

    /**
     * Get user tokens list by ID
     * @param int $userId
     * @return array
     */
    public function getUserTokens($userId);

    /**
     * Set user tokens list
     * @param int $userId
     * @param array $tokens
     */
    public function setUserTokens($userId, $tokens = []);

    /**
     * Add token to user list
     * @param int $userId
     * @param string $token
     */
    public function addUserToken($userId, $token);

    /**
     * Get user token content
     * @param string $token
     * @return array|null
     */
    public function getToken($token);

    /**
     * Set user token content
     * @param string   $token
     * @param array    $data
     * @param int|null $userId
     */
    public function setToken($token, $data = [], $userId = null);

    /**
     * Remove user token and its content
     * @param string $token
     * @param int|null $userId
     */
    public function removeToken($token, $userId = null);
}
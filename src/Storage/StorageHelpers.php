<?php

namespace DigitSoft\LaravelTokenAuth\Storage;

use DigitSoft\LaravelTokenAuth\Contracts\AccessToken;

/**
 * Trait StorageHelpers
 * @package DigitSoft\LaravelTokenAuth\Storage
 */
trait StorageHelpers
{
    /**
     * @var string|null
     */
    protected $_tokenPrefix;
    /**
     * @var string|null
     */
    protected $_userPrefix;

    /**
     * Get token storage key
     * @param \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|string $token
     * @return string
     */
    protected function getTokenKey($token)
    {
        return $this->getTokenKeyPrefix() . (string)$token;
    }

    /**
     * Get token storage keys (multiple)
     * @param \DigitSoft\LaravelTokenAuth\Contracts\AccessToken[]|string[] $tokens
     * @return string[]
     */
    protected function getTokenKeys($tokens)
    {
        $prefix = $this->getTokenKeyPrefix();
        $keys = [];
        foreach ($tokens as $token) {
            $keys[$token] = $prefix . (string)$token;
        }
        return $keys;
    }

    /**
     * Get user storage key
     * @param int $userId
     * @return string
     */
    protected function getUserKey($userId)
    {
        return $this->getUserKeyPrefix() . $userId;
    }

    /**
     * Get user storage for particular token key
     * @param int                                                      $user_id
     * @param \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|string $token
     * @return string
     */
    protected function getUserTokenKey($token, $user_id = null)
    {
        $user_id = $user_id === null && $token instanceof AccessToken ? $token->user_id : $user_id;

        return $this->getUserKey($user_id) . ':' . (string)$token;
    }

    /**
     * Get token key prefix from config.
     *
     * @return string
     */
    protected function getTokenKeyPrefix()
    {
        if (! isset($this->_tokenPrefix)) {
            $this->_tokenPrefix = config('auth-token.token_prefix', '');
        }

        return $this->_tokenPrefix;
    }

    /**
     * Get user key prefix from config.
     *
     * @return string
     */
    protected function getUserKeyPrefix()
    {
        if (! isset($this->_userPrefix)) {
            $this->_userPrefix = config('auth-token.user_prefix', '');
        }

        return $this->_userPrefix;
    }

    /**
     * Serialize data to json.
     *
     * @param  array $data
     * @return string
     */
    protected function serializeData($data = [])
    {
        return json_encode($data);
    }

    /**
     * Unserialize JSON data.
     *
     * @param  string $dataStr
     * @return array|null
     */
    protected function unserializeData($dataStr)
    {
        $data = @json_decode($dataStr, true);

        return is_array($data) ? $data : null;
    }
}

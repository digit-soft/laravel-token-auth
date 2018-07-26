<?php

namespace DigitSoft\LaravelTokenAuth\Storage;

use DigitSoft\LaravelTokenAuth\AccessToken;

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
     * Get token time to live
     * @return integer
     */
    protected function getTtl()
    {
        return config('auth-token.ttl', 60);
    }

    /**
     * Get token storage key
     * @param AccessToken|string $token
     * @return string
     */
    protected function getTokenKey($token)
    {
        return $this->getTokenKeyPrefix() . (string)$token;
    }

    /**
     * Get token storage keys (multiple)
     * @param AccessToken[]|string[] $tokens
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
     * Get token key prefix from config
     * @return string
     */
    protected function getTokenKeyPrefix()
    {
        if (!isset($this->_tokenPrefix)) {
            $this->_tokenPrefix = config('auth-token.token_prefix', '');
        }
        return $this->_tokenPrefix;
    }

    /**
     * Get user key prefix from config
     * @return string
     */
    protected function getUserKeyPrefix()
    {
        if (!isset($this->_userPrefix)) {
            $this->_userPrefix = config('auth-token.user_prefix', '');
        }
        return $this->_userPrefix;
    }

    /**
     * Serialize data to json
     * @param array $data
     * @return string
     */
    protected function serializeData($data = [])
    {
        return json_encode($data);
    }

    /**
     * Unserialize JSON data
     * @param string $dataStr
     * @return array|null
     */
    protected function unserializeData($dataStr)
    {
        $data = @json_decode($dataStr, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Convert list with token objects to string array
     * @param AccessToken[] $tokens
     * @return string[]
     */
    protected function stringifyTokensList($tokens = [], $preserveKeys = true)
    {
        $tokenIds = [];
        $num = 0;
        foreach ($tokens as $tokenKey => $token) {
            $key = $preserveKeys ? $tokenKey : $num;
            $tokenIds[$key] = (string)$token;
            $num++;
        }
        return $tokenIds;
    }
}
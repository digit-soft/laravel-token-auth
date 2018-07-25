<?php

namespace DigitSoft\LaravelTokenAuth\Storage;



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
     * @param string $token
     * @return string
     */
    protected function getTokenKey($token)
    {
        return $this->getTokenKeyPrefix() . $token;
    }

    /**
     * Get token storage keys (multiple)
     * @param string[] $tokens
     * @return string[]
     */
    protected function getTokenKeys($tokens)
    {
        $prefix = $this->getTokenKeyPrefix();
        $keys = [];
        foreach ($tokens as $token) {
            $keys[$token] = $prefix . $token;
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
        return $this->getTokenKeyPrefix() . $userId;
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
}
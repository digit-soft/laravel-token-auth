<?php

namespace DigitSoft\LaravelTokenAuth\Storage;

use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use Illuminate\Config\Repository;
use Illuminate\Redis\RedisManager;

/**
 * Class Redis
 * @package DigitSoft\LaravelTokenAuth\Storage
 */
class Redis implements Storage
{
    use StorageHelpers;

    /**
     * @var RedisManager
     */
    protected $manager;
    /**
     * @var string|null
     */
    protected $connection;

    /**
     * Redis storage constructor.
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->connection = $config->get('auth-token.connection', null);
        $this->manager = app()->refresh('redis', $this, 'setManager');
    }

    /**
     * @param RedisManager $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * Get manager connection
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function getConnection()
    {
        $this->manager->get('test');
        return $this->manager->connection($this->connection);
    }

    /**
     * Get user tokens list by ID
     * @param int $userId
     * @return array
     */
    public function getUserTokens($userId)
    {
        $userKey = $this->getUserKey($userId);
        $tokens = $this->getConnection()->lrange($userKey, 0, -1);
        $tokensCount = count($tokens);
        if (!empty($tokens)) {
            $this->filterTokens($tokens);
        }
        // Force write user tokens list
        if ($tokensCount !== count($tokens)) {
            $this->setUserTokens($userId, $tokens);
        }
        return $tokens;
    }

    /**
     * Set user tokens list
     * @param int   $userId
     * @param array $tokens
     */
    public function setUserTokens($userId, $tokens = [])
    {
        $tokens = array_unique($tokens);
        $userKey = $this->getUserKey($userId);
        $this->getConnection()->del([$userKey]);
        $this->getConnection()->rpush($userKey, $tokens);
    }

    /**
     * Add token to user list
     * @param int    $userId
     * @param string $token
     */
    public function addUserToken($userId, $token)
    {
        $userKey = $this->getUserKey($userId);
        $this->getConnection()->rpush($userKey, [$token]);
    }

    /**
     * Get user token content
     * @param string $token
     * @return array|null
     */
    public function getToken($token)
    {
        $key = $this->getTokenKey($token);
        $dataStr = $this->getConnection()->get($key);
        if (!empty($dataStr) && ($data = $this->unserializeData($dataStr)) !== null) {
            return $data;
        }
        return null;
    }

    /**
     * Set user token content
     * @param string   $token
     * @param array    $data
     * @param int|null $userId
     */
    public function setToken($token, $data = [], $userId = null)
    {
        $value = $this->serializeData($data);
        $key = $this->getTokenKey($token);
        $ttl = $this->getTtl();
        $this->getConnection()->setex($key, $ttl, $value);
        if (isset($userId)) {
            $userKey = $this->getUserKey($userId);
            $this->getConnection()->lpush($userKey, [$token]);
        }
    }

    /**
     * Remove user token and its content
     * @param string   $token
     * @param int|null $userId
     */
    public function removeToken($token, $userId = null)
    {
        $tokenKey = $this->getTokenKey($token);
        $this->getConnection()->expire($tokenKey, 0);
        if (isset($userId)) {
            $userKey = $this->getUserKey($userId);
            $this->getConnection()->lrem($userKey, 0, $token);
        }
    }

    /**
     * Filter not valid tokens
     * @param string[] $tokens
     */
    protected function filterTokens(&$tokens)
    {
        $tokenKeys = $this->getTokenKeys($tokens);
        $records = $this->getConnection()->mget($tokenKeys);
        foreach ($tokens as $num => $data) {
            if (!isset($records[$num])) {
                unset($tokens[$num]);
            }
        }
        $tokens = array_values($tokens);
    }
}
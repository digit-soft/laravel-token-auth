<?php

namespace DigitSoft\LaravelTokenAuth\Storage;

use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use Illuminate\Config\Repository;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Arr;
use DigitSoft\LaravelTokenAuth\Facades\AccessToken;

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
        return $this->manager->connection($this->connection);
    }

    /**
     * Get user tokens list by ID
     * @param int  $userId
     * @param bool $load
     * @return array|AccessTokenContract[]
     */
    public function getUserTokens($userId, $load = false)
    {
        $userKey = $this->getUserKey($userId);
        $tokens = $this->getConnection()->lrange($userKey, 0, -1);
        $tokensCount = count($tokens);
        if (empty($tokens)) {
            return [];
        }
        $this->filterTokens($tokens);
        // Force write user tokens list
        if ($tokensCount !== count($tokens)) {
            $this->setUserTokens($userId, $tokens);
        }
        // Load token objects
        if ($load) {
            $tokens = $this->getTokens($tokens);
        }
        return $tokens;
    }

    /**
     * Set user tokens list
     * @param int                    $userId
     * @param AccessTokenContract[]|string[] $tokens
     */
    public function setUserTokens($userId, $tokens = [])
    {
        $tokens = array_unique($tokens);
        $userKey = $this->getUserKey($userId);
        $this->getConnection()->del([$userKey]);
        $tokens = $this->stringifyTokensList($tokens, false);
        if (!empty($tokens)) {
            $this->getConnection()->rpush($userKey, $tokens);
        }
    }

    /**
     * Add token to user list
     * @param AccessTokenContract $token
     */
    public function addUserToken($token)
    {
        $userTokens = $this->getUserTokens($token->user_id, true);
        $userTokens[] = $token;
        $maxTime = now()->addYears(10)->timestamp;
        $userTokens = Arr::sort($userTokens, function ($value) use ($maxTime) {
            /** @var AccessTokenContract $value */
            return $value->exp !== null ? $value->exp : $maxTime;
        });
        $this->setUserTokens($token->user_id, $userTokens);
    }

    /**
     * Get user token content
     * @param string $tokenId
     * @return AccessTokenContract|null
     */
    public function getToken($tokenId)
    {
        $key = $this->getTokenKey($tokenId);
        $dataStr = $this->getConnection()->get($key);
        if (!empty($dataStr) && ($data = $this->unserializeData($dataStr)) !== null) {
            return AccessToken::createFromData($data);
        }
        return null;
    }

    /**
     * Get user tokens content (multiple)
     * @param string[] $tokenIds
     * @return AccessTokenContract[]
     */
    public function getTokens($tokenIds)
    {
        if (empty($tokenIds)) {
            return [];
        }
        $tokenKeys = $this->getTokenKeys($tokenIds);
        $rows = $this->getConnection()->mget($tokenKeys);
        $result = [];
        foreach ($rows as $index => $dataStr) {
            if (!isset($dataStr) || ($data = $this->unserializeData($dataStr)) === null) {
                continue;
            }
            $result[$tokenIds[$index]] = AccessToken::createFromData($data);
        }
        return $result;
    }

    /**
     * Set user token content
     * @param AccessTokenContract $token
     */
    public function setToken($token)
    {
        if (isset($token->ttl) && $token->ttl <= 0) {
            $this->removeToken($token);
            return;
        }
        $value = $this->serializeData($token->toArray());
        $key = $this->getTokenKey($token);
        if ($token->ttl !== null) {
            $this->getConnection()->setex($key, $token->ttl, $value);
        } else {
            $this->getConnection()->set($key, $value);
        }
        $this->addUserToken($token);
    }

    /**
     * Remove user token and its content
     * @param AccessTokenContract $token
     */
    public function removeToken($token)
    {
        $tokenKey = $this->getTokenKey($token);
        $this->getConnection()->expire($tokenKey, 0);
        $userKey = $this->getUserKey($token->user_id);
        $this->getConnection()->lrem($userKey, 0, $token);
    }

    /**
     * Check that token record exists in storage
     * @param string $token
     * @return bool
     */
    public function tokenExists($token)
    {
        $key = $this->getTokenKey($token);
        $exists = (int)$this->getConnection()->exists($key);
        return $exists > 0;
    }

    /**
     * Filter not valid tokens
     * @param string[] $tokenIds
     */
    protected function filterTokens(&$tokenIds)
    {
        $tokenKeys = $this->getTokenKeys($tokenIds);
        $records = $this->getConnection()->mget($tokenKeys);
        foreach ($tokenIds as $num => $data) {
            if (!isset($records[$num])) {
                unset($tokenIds[$num]);
            }
        }
        $tokenIds = array_values($tokenIds);
    }
}
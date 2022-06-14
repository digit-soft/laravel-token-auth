<?php

namespace DigitSoft\LaravelTokenAuth\Storage;

use Illuminate\Config\Repository;
use Illuminate\Redis\RedisManager;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Facades\TokenCached;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;

/**
 * Redis storage component.
 */
class Redis implements Storage
{
    use StorageHelpers;

    /**
     * @var \Illuminate\Redis\RedisManager
     */
    protected $manager;
    /**
     * @var string|null
     */
    protected ?string $connection;

    /**
     * Redis storage constructor.
     *
     * @param  \Illuminate\Config\Repository  $config
     * @param  \Illuminate\Redis\RedisManager $manager
     */
    public function __construct(Repository $config, RedisManager $manager)
    {
        $this->connection = $config->get('auth-token.connection');
        $this->manager = $manager;
    }

    /**
     * Set REDIS manager.
     *
     * @param  RedisManager $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * Get manager connection.
     *
     * @return \Illuminate\Redis\Connections\Connection|\Illuminate\Redis\Connections\PredisConnection
     */
    public function getConnection()
    {
        return $this->manager->connection($this->connection);
    }

    /**
     * Get user tokens list by ID.
     *
     * @param  int  $user_id
     * @param  bool $load
     * @return AccessTokenContract[]
     */
    public function getUserTokens($user_id, bool $load = false): array
    {
        $userKey = $this->getUserKey($user_id);
        $tokens = $this->getConnection()->keys($userKey . ':*');
        $prefixLn = strlen($userKey) + 1;
        array_walk($tokens, function (&$value) use ($prefixLn) {
            $value = substr($value, $prefixLn);
        });
        if (empty($tokens)) {
            return [];
        }
        $tokensMissing = $this->filterTokens($tokens, true);
        if (! empty($tokensMissing)) {
            $keysToRemove = [];
            foreach ($tokensMissing as $tokenId) {
                $keysToRemove[] = $this->getUserTokenKey($tokenId, $user_id);
            }
            $this->getConnection()->del($keysToRemove);
            $tokens = array_values(array_diff($tokens, $tokensMissing));
        }
        // Load token objects
        if ($load) {
            $tokens = $this->getTokens($tokens);
        }

        return $tokens;
    }

    /**
     * Set user => tokens assignments list.
     *
     * @param  int                   $user_id
     * @param  AccessTokenContract[] $tokens
     */
    public function setUserTokens($user_id, array $tokens = []): void
    {
        $existingKeys = $this->getUserTokenStorageKeys($user_id);
        // Remove old [user => token] keys
        if (! empty($existingKeys)) {
            $this->getConnection()->del($existingKeys);
        }
        if (empty($tokens)) {
            return;
        }
        $tokens = array_unique($tokens);
        $tokensToSet = [];
        $tokensToExpire = [];
        $now = now()->timestamp;
        /** @var AccessTokenContract $token */
        foreach ($tokens as $token) {
            $key = $this->getUserTokenKey($token, $user_id);
            $value = (string)$token;
            if (isset($token->exp)) {
                $tokensToExpire[$key] = $token->exp - $now;
            }
            $tokensToSet[$key] = $value;
        }
        $this->getConnection()->mset($tokensToSet);
        foreach ($tokensToExpire as $key => $exp) {
            $this->getConnection()->expire($key, $exp);
        }
    }

    /**
     * Get user token content.
     *
     * @param  string $tokenId
     * @return AccessTokenContract|null
     */
    public function getToken($tokenId): ?AccessTokenContract
    {
        $key = $this->getTokenKey($tokenId);
        $dataStr = $this->getConnection()->get($key);
        if (! empty($dataStr) && ($data = $this->unserializeData($dataStr)) !== null) {
            return TokenCached::createFromData($data, true);
        }

        return null;
    }

    /**
     * Get user tokens content (multiple).
     *
     * @param  string[] $tokenIds
     * @return AccessTokenContract[]
     */
    public function getTokens($tokenIds): array
    {
        if (empty($tokenIds)) {
            return [];
        }

        $tokenKeys = $this->getTokenKeys($tokenIds);
        $rows = $this->getConnection()->mget($tokenKeys);
        $result = [];
        foreach ($rows as $index => $dataStr) {
            if (! isset($dataStr) || ($data = $this->unserializeData($dataStr)) === null) {
                continue;
            }
            $result[$tokenIds[$index]] = TokenCached::createFromData($data, true);
        }

        return $result;
    }

    /**
     * Set user token content.
     *
     * @param  AccessTokenContract $token
     * @return bool
     */
    public function setToken($token): bool
    {
        $ttl = $this->getTokenRealTtl($token);
        if (isset($ttl) && $ttl <= 0) {
            $this->removeToken($token);

            return false;
        }

        $value = $this->serializeData($token->toArray(true));
        $key = $this->getTokenKey($token);
        if ($ttl !== null) {
            $this->getConnection()->setex($key, $ttl, $value);
            $this->addUserToken($token, $ttl);
        } else {
            $this->getConnection()->set($key, $value);
            $this->addUserToken($token);
        }

        return true;
    }

    /**
     * Remove user token and its content.
     *
     * @param  AccessTokenContract $token
     * @return bool
     */
    public function removeToken($token): bool
    {
        $tokenKey = $this->getTokenKey($token);
        $this->getConnection()->expire($tokenKey, 0);
        $this->removeUserToken($token);

        return true;
    }

    /**
     * Check that token record exists in storage.
     *
     * @param  AccessTokenContract|string $token
     * @return bool
     */
    public function tokenExists($token): bool
    {
        $key = $this->getTokenKey($token);
        $exists = (int)$this->getConnection()->exists($key);

        return $exists > 0;
    }

    /**
     * Add token to user list
     *
     * @param  AccessTokenContract $token
     * @param  int|null            $ttl
     */
    protected function addUserToken(AccessTokenContract $token, ?int $ttl = null): void
    {
        if ($token->isGuest()) {
            return;
        }
        $key = $this->getUserTokenKey($token);
        if (isset($ttl)) {
            $this->getConnection()->setex($key, $ttl, (string)$token);
        } else {
            $this->getConnection()->set($key, (string)$token);
        }
    }

    /**
     * Remove user => token assignment.
     *
     * @param  AccessTokenContract $token
     */
    protected function removeUserToken(AccessTokenContract $token): void
    {
        $key = $this->getUserTokenKey($token);
        $this->getConnection()->del([$key]);
    }

    /**
     * Filter not valid tokens.
     *
     * @param  string[] $tokenIds
     * @param  bool     $returnMissing
     * @return array
     */
    protected function filterTokens(array $tokenIds, bool $returnMissing = false): array
    {
        $tokenKeys = $this->getTokenKeys($tokenIds);
        $records = $this->getConnection()->mget($tokenKeys);
        $missing = [];
        $existing = [];
        foreach ($tokenIds as $num => $data) {
            if (! isset($records[$num])) {
                $missing[] = $data;
                continue;
            }
            $existing[] = $data;
        }

        return $returnMissing ? $missing : $existing;
    }

    /**
     * Get user token keys.
     *
     * @param  int $user_id
     * @return array
     */
    protected function getUserTokenStorageKeys($user_id): array
    {
        $key = $this->getUserKey($user_id);

        return $this->getConnection()->keys($key . ':*');
    }

    /**
     * Get AccessToken real TTL
     *
     * @param  AccessTokenContract $token
     * @return int|null
     */
    protected function getTokenRealTtl(AccessTokenContract $token): ?int
    {
        $now = time();
        if (! isset($token->ttl)) {
            return null;
        }
        if (isset($token->exp)) {
            $ttl = (int)($token->exp - $now);
        } else {
            $token->exp = (int)($now + $token->ttl);
            $ttl = $token->ttl;
        }

        return $ttl > 0 ? $ttl : -1;
    }
}

<?php

namespace DigitSoft\LaravelTokenAuth;

use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Events\AccessTokenCreated;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Queue\SerializesModels;

/**
 * Class AccessToken
 * @package DigitSoft\LaravelTokenAuth
 */
class AccessToken implements AccessTokenContract, Jsonable, Arrayable
{
    use SerializesModels;

    /**
     * User ID
     * @var int
     */
    public $user_id;
    /**
     * Token value
     * @var string
     */
    public $token;
    /**
     * Token issued at time
     * @var int|null
     */
    public $iat;
    /**
     * Token time to live
     * @var int|null
     */
    public $ttl;
    /**
     * Token expire time
     * @var int|null
     */
    public $exp;
    /**
     * Token client ID
     * @var string
     */
    public $client_id = self::CLIENT_ID_DEFAULT;

    /**
     * Cached reflection class
     * @var \ReflectionClass
     */
    protected $reflection;
    /**
     * @var Storage
     */
    protected $storage;

    /**
     * Token constructor.
     * @param array   $config
     * @param Storage $storage
     */
    public function __construct($config = [], Storage $storage)
    {
        $this->configureSelf($config);
        $this->storage = $storage;
    }

    /**
     * Set time to live for token
     * @param int  $ttl
     * @param bool $overwriteTimestamps
     */
    public function setTtl($ttl = 60, $overwriteTimestamps = true)
    {
        $this->ttl = $ttl;
        if ($overwriteTimestamps) {
            $this->iat = now()->timestamp;
            $this->exp = $this->ttl ? $this->iat + $this->ttl : null;
        }
    }

    /**
     * Check that token was expired
     * @return bool
     */
    public function isExpired()
    {
        return isset($this->ttl) && isset($this->exp) && $this->exp < now()->timestamp;
    }

    /**
     * Save token to storage
     */
    public function save()
    {
        if (!isset($this->iat)) {
            $this->iat = now()->timestamp;
        }
        $this->storage->setToken($this);
    }

    /**
     * Remove token from storage
     */
    public function remove()
    {
        $this->storage->removeToken($this);
    }

    /**
     * @inheritdoc
     */
    public function toJson($options = 0)
    {
        $data = $this->toArray();
        return json_encode($data, $options);
    }

    /**
     * @inheritdoc
     */
    public function toArray()
    {
        try {
            $reflection = $this->reflection ?? $this->reflection = new \ReflectionClass($this);
            // @codeCoverageIgnoreStart
        } catch (\ReflectionException $exception) {
            return [];
        }
        // @codeCoverageIgnoreEnd
        $data = [];
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $propertyName = $property->getName();
                $data[$propertyName] = $this->{$propertyName};
            }
        }
        return $data;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->token;
    }

    /**
     * Setter for storage
     * @param Storage $storage
     */
    public function setStorage(Storage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Getter for storage
     * @return Storage
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Get last added user token
     * @param Authenticatable $user
     * @param string          $client_id
     * @param Storage|null    $storage
     * @return AccessToken|null
     */
    public static function getFirstFor(Authenticatable $user, $client_id = self::CLIENT_ID_DEFAULT, Storage $storage = null)
    {
        if ($storage === null) {
            $storage = app()->make(Storage::class);
        }
        $userId = $user->getAuthIdentifier();
        $list = $storage->getUserTokens($userId, true);
        if (empty($list)) {
            return null;
        }
        foreach ($list as $token) {
            if ($token->client_id === $client_id) {
                return $token;
            }
        }
        return null;
    }

    /**
     * Create new token for user
     * @param Authenticatable $user
     * @param string          $client_id
     * @param bool            $autoTTl
     * @return AccessToken
     */
    public static function createFor(Authenticatable $user, $client_id = self::CLIENT_ID_DEFAULT, $autoTTl = true)
    {
        $data = [
            'user_id' => $user->getAuthIdentifier(),
            'client_id' => $client_id,
        ];
        $token = static::createFromData($data);
        $token->forceUnique();
        if ($autoTTl) {
            $token->setTtl(config('auth-token.ttl'));
        }
        $event = new AccessTokenCreated($token);
        event($event);
        return $token;
    }

    /**
     * Create token instance from data array
     * @param array $data
     * @return AccessToken
     */
    public static function createFromData($data = [])
    {
        return app()->make(AccessTokenContract::class, ['config' => $data]);
    }

    /**
     * Configure object
     * @param array $config
     */
    protected function configureSelf($config = [])
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Force token uniqueness
     * @return $this
     */
    protected function forceUnique()
    {
        if ($this->token === null) {
            $this->token = $this->generateTokenId();
        }
        while ($this->storage->tokenExists($this->token)) {
            $this->token = $this->generateTokenId();
        }
        return $this;
    }

    /**
     * Generate random string
     * @return string
     */
    protected function generateTokenId()
    {
        return str_random(config('auth-token.token_length', 60));
    }
}
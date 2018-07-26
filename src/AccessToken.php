<?php

namespace DigitSoft\LaravelTokenAuth;

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
class AccessToken implements Jsonable, Arrayable
{
    use SerializesModels;

    const CLIENT_ID_DEFAULT = 'api';

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
     * Token constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->configureSelf($config);
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
        $this->setTtl(config('auth-token.ttl'));
        static::getTokensStorage()->setToken($this);
    }

    /**
     * Remove token from storage
     */
    public function remove()
    {
        static::getTokensStorage()->removeToken($this);
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
        } catch (\ReflectionException $exception) {
            return [];
        }
        $data = [];
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $propertyName = $property->getName();
            $data[$propertyName] = $this->{$propertyName};
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
     * Get last added user token
     * @param Authenticatable $user
     * @param string          $client_id
     * @return AccessToken|null
     */
    public static function getFirstFor(Authenticatable $user, $client_id = self::CLIENT_ID_DEFAULT)
    {
        $userId = $user->getAuthIdentifier();
        $list = static::getTokensStorage()->getUserTokens($userId, true);
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
     * @return AccessToken
     */
    public static function createFor(Authenticatable $user, $client_id = self::CLIENT_ID_DEFAULT)
    {
        do {
            $tokenStr = str_random(60);
        } while (static::getTokensStorage()->tokenExists($tokenStr));
        $data = [
            'user_id' => $user->getAuthIdentifier(),
            'token' => $tokenStr,
            'client_id' => $client_id,
        ];
        $token = static::createTokenObject($data);
        $token->setTtl(config('auth-token.ttl'));
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
        return static::createTokenObject($data);
    }

    /**
     * @return Storage
     */
    protected static function getTokensStorage()
    {
        return app('auth.tokencached.storage');
    }

    /**
     * Create token object
     * @param array $config
     * @return AccessToken
     */
    protected static function createTokenObject($config = [])
    {
        $className = config('auth-token.token_class');
        return new $className($config);
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
}
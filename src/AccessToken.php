<?php

namespace DigitSoft\LaravelTokenAuth;

use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use Illuminate\Queue\SerializesModels;

/**
 * Class AccessToken
 * @package DigitSoft\LaravelTokenAuth
 */
class AccessToken implements AccessTokenContract
{
    use SerializesModels;

    /**
     * User ID
     * @var int
     */
    public $user_id = self::USER_ID_GUEST;
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
    public $client_id;
    /**
     * Session data serialized
     * @var string
     */
    public $session;

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
     * Guarded properties
     * @var array
     */
    protected $guarded = ['session'];

    /**
     * Token constructor.
     * @param Storage $storage
     * @param array   $config
     */
    public function __construct(Storage $storage, $config = [])
    {
        $this->client_id = Facades\AccessToken::getDefaultClientId();
        $this->session = serialize([]);
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
     * Check that this is guest token
     * @return bool
     */
    public function isGuest()
    {
        return empty($this->user_id);
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
    public function regenerate($save = false)
    {
        if ($save) {
            $this->remove();
        }
        $this->token = null;
        $this->ensureUniqueness();
        $this->setTtl($this->ttl, true);
        if ($save) {
            $this->save();
        }
    }

    /**
     * @inheritdoc
     */
    public function toJson($options = 0, $withGuarded = false)
    {
        $data = $this->toArray($withGuarded);
        return json_encode($data, $options);
    }

    /**
     * @inheritdoc
     */
    public function toArray($withGuarded = false)
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
            $propertyName = $property->getName();
            if (!$property->isStatic() && ($withGuarded || !in_array($propertyName, $this->guarded))) {
                $data[$propertyName] = $this->{$propertyName};
            }
        }
        return $data;
    }

    /**
     * Return object string representation
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
     * Force token unique check and ID regeneration
     * @return $this
     */
    public function ensureUniqueness()
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
     * Generate random string
     * @return string
     */
    protected function generateTokenId()
    {
        return str_random(config('auth-token.token_length', 60));
    }
}
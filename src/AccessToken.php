<?php

namespace DigitSoft\LaravelTokenAuth;

use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Facades\TokenCached;
use DigitSoft\LaravelTokenAuth\Traits\TracksPropertiesChanges;
use Illuminate\Queue\SerializesModels;

/**
 * Class AccessToken
 * @package DigitSoft\LaravelTokenAuth
 * @OA\Property("user_id",example=1,description="User ID")
 * @OA\Property("token",type="string",description="Token string")
 * @OA\Property("iat",type="integer",example=19200000,description="Issued at time")
 * @OA\Property("ttl",type="integer",example=8600,description="Time to live")
 * @OA\Property("exp",type="integer",example=19200000,description="Expire time")
 * @OA\Property("client_id",type="string",example="api",description="Client ID")
 */
class AccessToken implements AccessTokenContract
{
    use SerializesModels, TracksPropertiesChanges;

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
     * Saved in storage or not
     * @var bool
     */
    protected $saved = false;

    /**
     * Token constructor.
     * @param Storage $storage
     * @param array   $config
     * @param bool    $fromStorage
     */
    public function __construct(Storage $storage, $config = [], $fromStorage = false)
    {
        $this->client_id = Facades\TokenCached::getDefaultClientId();
        $this->session = serialize([]);
        $this->configureSelf($config);
        $this->storage = $storage;
        if ($fromStorage) {
            $this->saved = true;
            $this->rememberState();
        }
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
     * @return bool
     */
    public function save()
    {
        if (!isset($this->iat)) {
            $this->iat = now()->timestamp;
        }
        if($this->needToSave() && $this->storage->setToken($this)) {
            $this->saved = true;
            $this->rememberState();
        }
        return $this->saved;
    }

    /**
     * Get saved flag
     * @return bool
     */
    public function saved()
    {
        return $this->saved;
    }

    /**
     * Remove token from storage
     * @return bool
     */
    public function remove()
    {
        return $this->storage->removeToken($this);
    }

    /**
     * Regenerate token
     * @param bool $save
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
            $this->token = TokenCached::generateTokenStr();
        }
        while ($this->storage->tokenExists($this->token)) {
            $this->token = TokenCached::generateTokenStr();
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
     * Check that object need to be saved to storage
     * @return bool
     */
    protected function needToSave()
    {
        return !$this->saved || $this->isChanged();
    }
}

<?php

namespace DigitSoft\LaravelTokenAuth;

use Illuminate\Queue\SerializesModels;
use DigitSoft\LaravelTokenAuth\Facades\TokenCached;
use DigitSoft\LaravelTokenAuth\Traits\WithAuthTokenStorage;
use DigitSoft\LaravelTokenAuth\Traits\TracksPropertiesChanges;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;

/**
 * Class AccessToken
 *
 * @OA\Property("user_id", example=1, description="User ID")
 * @OA\Property("token", type="string", description="Token string")
 * @OA\Property("iat", type="integer", example=19200000, description="Issued at time")
 * @OA\Property("ttl", type="integer", example=8600, description="Time to live")
 * @OA\Property("exp", type="integer", example=19200000, description="Expire time")
 * @OA\Property("client_id", type="string", example="api", description="Client ID")
 */
class AccessToken implements AccessTokenContract
{
    use SerializesModels, TracksPropertiesChanges, WithAuthTokenStorage;

    /**
     * User ID
     *
     * @var int
     */
    public int $user_id = AccessTokenContract::USER_ID_GUEST;
    /**
     * Token value
     *
     * @var string|null
     */
    public ?string $token = null;
    /**
     * Token issued at time
     *
     * @var int|null
     */
    public ?int $iat = null;
    /**
     * Token time to live
     *
     * @var int|null
     */
    public ?int $ttl = null;
    /**
     * Token expire time
     *
     * @var int|null
     */
    public ?int $exp = null;
    /**
     * Token client ID
     *
     * @var string
     */
    public string $client_id;
    /**
     * Session data serialized
     *
     * @var string
     */
    public string $session;

    /**
     * Guarded properties
     *
     * @var array
     */
    protected array $guarded = ['session'];
    /**
     * Saved in storage or not
     *
     * @var bool
     */
    protected bool $saved = false;

    /**
     * Reflection for class
     *
     * @var \ReflectionClass|null
     */
    protected ?\ReflectionClass $_reflection = null;

    /**
     * Token constructor.
     *
     * @param  array   $config
     * @param  bool    $fromStorage
     */
    public function __construct(array $config = [], bool $fromStorage = false)
    {
        $this->client_id = Facades\TokenCached::getDefaultClientId();
        $this->session = serialize([]);
        $this->configureSelf($config);
        if ($fromStorage) {
            $this->saved = true;
            $this->rememberState();
        }
    }

    /**
     * Set time to live for token.
     *
     * @param  int|null $ttl
     * @param  bool     $overwriteTimestamps
     * @return \DigitSoft\LaravelTokenAuth\AccessToken
     */
    public function setTtl(?int $ttl = 60, bool $overwriteTimestamps = true): static
    {
        $this->ttl = $ttl;
        if ($overwriteTimestamps) {
            $this->iat = now()->timestamp;
            $this->exp = $this->ttl !== null ? $this->iat + $this->ttl : null;
        }

        return $this;
    }

    /**
     * Check that token was expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return isset($this->ttl, $this->exp) && $this->exp < now()->timestamp;
    }

    /**
     * Check that this is guest token
     *
     * @return bool
     */
    public function isGuest(): bool
    {
        return empty($this->user_id);
    }

    /**
     * Get fresh instance of token.
     *
     * @return \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|null
     */
    public function fresh(): ?static
    {
        if ($this->token === null) {
            return null;
        }

        return static::tokenStorage()->getToken($this->token);
    }

    /**
     * Save token to storage.
     *
     * @return bool
     */
    public function save(): bool
    {
        if (! isset($this->iat)) {
            $this->iat = now()->timestamp;
        }
        if ($this->needToSave() && static::tokenStorage()->setToken($this)) {
            $this->saved = true;
            $this->rememberState();
        }

        return $this->saved;
    }

    /**
     * Get saved flag.
     *
     * @return bool
     */
    public function wasSaved(): bool
    {
        return $this->saved;
    }

    /**
     * Remove token from storage.
     *
     * @return bool
     */
    public function remove(): bool
    {
        return static::tokenStorage()->removeToken($this);
    }

    /**
     * Regenerate token.
     *
     * @param  bool $save
     * @return bool
     */
    public function regenerate(bool $save = false): bool
    {
        if ($save) {
            $this->remove();
        }

        $this->token = null;
        $this->ensureUniqueness();
        $this->setTtl($this->ttl, true);

        return ! $save || $this->save();
    }

    /**
     * {@inheritdoc}
     */
    public function toJson($options = 0, $withGuarded = false): string
    {
        $data = $this->toArray($withGuarded);

        return json_encode($data, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(bool $withGuarded = false): array
    {
        try {
            $reflection = $this->getRef();
            // @codeCoverageIgnoreStart
        } catch (\ReflectionException $exception) {
            return [];
        }
        // @codeCoverageIgnoreEnd
        $data = [];
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();
            if (! $property->isStatic() && ($withGuarded || ! in_array($propertyName, $this->guarded, true))) {
                $data[$propertyName] = $this->{$propertyName};
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(bool $withGuarded = false): mixed
    {
        return $this->toArray($withGuarded);
    }

    /**
     * Return object string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->token;
    }

    /**
     * Force token unique check and ID regeneration.
     *
     * @return $this
     */
    public function ensureUniqueness(): static
    {
        if ($this->token === null) {
            $this->token = TokenCached::generateTokenStr();
        }
        while (static::tokenStorage()->tokenExists($this->token)) {
            $this->token = TokenCached::generateTokenStr();
        }

        return $this;
    }

    /**
     * Configure object.
     *
     * @param  array $config
     */
    protected function configureSelf(array $config = []): void
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Check that object need to be saved to storage.
     *
     * @return bool
     */
    protected function needToSave(): bool
    {
        return ! $this->saved || $this->isChanged();
    }

    /**
     * Get current class reflection.
     *
     * @return \ReflectionClass
     * @throws null
     */
    protected function getRef(): \ReflectionClass
    {
        return $this->_reflection ?? new \ReflectionClass($this);
    }
}

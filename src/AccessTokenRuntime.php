<?php

namespace DigitSoft\LaravelTokenAuth;

use Illuminate\Contracts\Auth\Authenticatable;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;

class AccessTokenRuntime implements AccessTokenContract
{
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
     * Saved in storage or not
     *
     * @var bool
     */
    protected bool $saved = false;

    public function __construct()
    {
        $this->session = serialize([]);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function isExpired(): bool
    {
        return isset($this->ttl, $this->exp) && $this->exp < now()->timestamp;
    }

    /**
     * {@inheritdoc}
     */
    public function isGuest(): bool
    {
        return empty($this->user_id);
    }

    /**
     * {@inheritdoc}
     */
    public function fresh(): ?static
    {
        // Does nothing
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function save(): bool
    {
        return $this->saved = true;
    }

    /**
     * {@inheritdoc}
     */
    public function wasSaved(): bool
    {
        return $this->saved;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(): bool
    {
        // Does nothing
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function regenerate(bool $save = false): bool
    {
        // Does nothing
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function ensureUniqueness(): static
    {
        // Does nothing
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toJson($options = 0, bool $withGuarded = false): string
    {
        return json_encode($this->toArray($withGuarded), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(bool $withGuarded = false): mixed
    {
        return $this->toArray($withGuarded);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(bool $withGuarded = false): array
    {
        return [
            'iat' => $this->iat,
            'ttl' => $this->ttl,
            'exp' => $this->exp,
            'token' => $this->token,
            'client_id' => $this->client_id,
        ];
    }

    /**
     * Make a new instance for user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string|null                                $token
     * @param  string|null                                $client_id
     * @return static
     */
    public static function makeForUser(Authenticatable $user, ?string $token = null, ?string $client_id = null): static
    {
        $instance = new static();
        $instance->user_id = $user->getAuthIdentifier();
        $instance->token = $token ?? Facades\TokenCached::generateTokenStr();
        $instance->client_id = $client_id ?? Facades\TokenCached::getDefaultClientId();

        return $instance;
    }
}

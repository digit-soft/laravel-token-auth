<?php

namespace DigitSoft\LaravelTokenAuth\Contracts;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use DigitSoft\LaravelTokenAuth\Traits\TracksPropertiesChanges;

/**
 * Interface AccessToken
 *
 * @package DigitSoft\LaravelTokenAuth\Contracts
 * @property string|null $token      Token value
 * @property int|null    $user_id    User ID
 * @property string      $client_id  Token client ID
 * @property int|null    $iat        Token issued at time
 * @property int|null    $exp        Token expire time
 * @property int|null    $ttl        Token time to live
 * @property string|null $session    Token session data
 * @mixin TracksPropertiesChanges
 */
interface AccessToken extends Jsonable, Arrayable
{
    const USER_ID_GUEST = 0;

    const REQUEST_CLIENT_ID_HEADER = 'Auth-client-Id';
    const REQUEST_CLIENT_ID_PARAM = 'auth_client_id';

    /**
     * Set time to live for token
     *
     * @param  int  $ttl
     * @param  bool $overwriteTimestamps
     */
    public function setTtl(int $ttl = 60, bool $overwriteTimestamps = true);

    /**
     * Check that token was expired
     *
     * @return bool
     */
    public function isExpired(): bool;

    /**
     * Check that this is guest token
     *
     * @return bool
     */
    public function isGuest(): bool;

    /**
     * Get fresh instance of token.
     *
     * @return \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|null
     */
    public function fresh(): ?static;

    /**
     * Save token to storage
     *
     * @return bool
     */
    public function save(): bool;

    /**
     * Get saved flag
     *
     * @return bool
     */
    public function wasSaved(): bool;

    /**
     * Remove token from storage
     *
     * @return bool
     */
    public function remove(): bool;

    /**
     * Regenerate token
     *
     * @param  bool $save
     */
    public function regenerate(bool $save = false): bool;

    /**
     * Force token unique check and ID regeneration
     *
     * @return $this
     */
    public function ensureUniqueness();

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @param bool $withGuarded  Include guarded properties
     * @return string
     */
    public function toJson($options = 0, bool $withGuarded = false);

    /**
     * Get the instance as an array.
     *
     * @param bool $withGuarded  Include guarded properties
     * @return array
     */
    public function toArray(bool $withGuarded = false): array;
}

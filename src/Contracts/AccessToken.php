<?php

namespace DigitSoft\LaravelTokenAuth\Contracts;

use DigitSoft\LaravelTokenAuth\Traits\TracksPropertiesChanges;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * Interface AccessToken
 * @package DigitSoft\LaravelTokenAuth\Contracts
 * @property string|null $token  Token value
 * @property int|null    $user_id  User ID
 * @property string      $client_id  Token client ID
 * @property int|null    $iat  Token issued at time
 * @property int|null    $exp  Token expire time
 * @property int|null    $ttl  Token time to live
 * @property string|null $session  Token session data
 * @mixin TracksPropertiesChanges
 */
interface AccessToken extends Jsonable, Arrayable
{
    const USER_ID_GUEST = 0;

    const REQUEST_CLIENT_ID_HEADER = 'Auth-client-Id';
    const REQUEST_CLIENT_ID_PARAM = 'auth_client_id';

    /**
     * Set time to live for token
     * @param int  $ttl
     * @param bool $overwriteTimestamps
     */
    public function setTtl($ttl = 60, $overwriteTimestamps = true);

    /**
     * Check that token was expired
     * @return bool
     */
    public function isExpired();

    /**
     * Check that this is guest token
     * @return bool
     */
    public function isGuest();

    /**
     * Save token to storage
     * @return bool
     */
    public function save();

    /**
     * Get saved flag
     * @return bool
     */
    public function saved();

    /**
     * Remove token from storage
     * @return bool
     */
    public function remove();

    /**
     * Regenerate token
     * @param bool $save
     */
    public function regenerate($save = false);

    /**
     * Setter storage
     * @param Storage $storage
     */
    public function setStorage(Storage $storage);

    /**
     * Getter for storage
     * @return Storage
     */
    public function getStorage();

    /**
     * Force token unique check and ID regeneration
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
    public function toJson($options = 0, $withGuarded = false);

    /**
     * Get the instance as an array.
     *
     * @param bool $withGuarded  Include guarded properties
     * @return array
     */
    public function toArray($withGuarded = false);
}
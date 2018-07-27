<?php

namespace DigitSoft\LaravelTokenAuth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interface AccessToken
 * @package DigitSoft\LaravelTokenAuth\Contracts
 */
interface AccessToken
{
    const CLIENT_ID_DEFAULT = 'api';

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
     * Save token to storage
     */
    public function save();

    /**
     * Remove token from storage
     */
    public function remove();

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
     * Get last added user token
     * @param Authenticatable $user
     * @param string          $client_id
     * @param Storage|null    $storage
     * @return AccessToken|null
     */
    public static function getFirstFor(Authenticatable $user, $client_id = self::CLIENT_ID_DEFAULT, Storage $storage = null);

    /**
     * Create new token for user
     * @param Authenticatable $user
     * @param string          $client_id
     * @param bool            $autoTTl
     * @return AccessToken
     */
    public static function createFor(Authenticatable $user, $client_id = self::CLIENT_ID_DEFAULT, $autoTTl = true);

    /**
     * Create token instance from data array
     * @param array $data
     * @return AccessToken
     */
    public static function createFromData($data = []);
}
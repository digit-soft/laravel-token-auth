<?php

namespace DigitSoft\LaravelTokenAuth\Tests;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Support\Str;

/**
 * Class UserProvider. Uses array as repository
 * @package DigitSoft\LaravelTokenAuth\Tests
 */
class UserProvider extends EloquentUserProvider
{
    public $usersArray = [];

    /**
     * @inheritdoc
     */
    public function retrieveById($identifier)
    {
        $idColumn = $this->createModel()->getAuthIdentifierName();
        return $this->retrieveByCredentialsInternal([$idColumn => $identifier]);
    }

    /**
     * @inheritdoc
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) ||
            (count($credentials) === 1 &&
                array_key_exists('password', $credentials))) {
            return;
        }

        $credentialsFinal = [];

        foreach ($credentials as $key => $value) {
            if (Str::contains($key, 'password')) {
                continue;
            }
            $credentialsFinal[$key] = $value;
        }

        return $this->retrieveByCredentialsInternal($credentialsFinal);
    }

    /**
     * @inheritdoc
     */
    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function updateRememberToken(UserContract $user, $token)
    {
        return null;
    }

    protected function retrieveByCredentialsInternal(array $credentials)
    {
        foreach ($this->usersArray as $userData) {
            $matched = true;
            foreach ($credentials as $key => $value) {
                if ($value !== $userData[$key]) {
                    $matched = false;
                    break;
                }
            }
            if ($matched) {
                $model = $this->createModel();
                $model->fill($userData);
                return $model;
            }
        }
        return null;
    }

    /**
     * Add user to array
     * @param array $userData
     * @param int   $id
     */
    public function addUserToArray(array $userData, int $id)
    {
        $this->usersArray[$id] = $userData;
    }

    /**
     * Clear users list
     */
    public function clearUsersArray()
    {
        $this->usersArray = [];
    }
}
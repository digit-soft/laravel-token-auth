<?php

namespace DigitSoft\LaravelTokenAuth;

use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Auth\EloquentUserProvider as BaseEloquentUserProvider;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;

/**
 * User provider but with guest models.
 */
class EloquentUserProvider extends BaseEloquentUserProvider
{
    /**
     * Model class for a guest.
     * @var string
     */
    protected $modelGuest;

    /**
     * EloquentUserProvider constructor.
     *
     * @param  \Illuminate\Contracts\Hashing\Hasher $hasher
     * @param  string                               $model
     * @param  string                               $modelGuest
     */
    public function __construct(HasherContract $hasher, $model, $modelGuest)
    {
        $this->modelGuest = $modelGuest;

        parent::__construct($hasher, $model);
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        if ($identifier === AccessTokenContract::USER_ID_GUEST) {
            return $this->createModelGuest();
        }

        return parent::retrieveById($identifier);
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Contracts\Auth\Authenticatable
     */
    public function createModelGuest()
    {
        $class = '\\' . ltrim($this->modelGuest, '\\');

        return new $class;
    }
}

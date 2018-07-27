<?php

namespace DigitSoft\LaravelTokenAuth\Tests;

use DigitSoft\LaravelTokenAuth\AccessToken;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;
use DigitSoft\LaravelTokenAuth\Contracts\Storage as StorageContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $token_id;

    protected $token_ttl;

    protected $token_user_id;

    protected $token_user_id_fake;

    protected $token_client_id;

    /**
     * @inheritdoc
     */
    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->token_id = '4BaoPSOuvasGj55BUJluikbbSC9eoaZk2Z3tI7kQB56hkp7xGNRQxfMfBMB0';
        $this->token_client_id = AccessTokenContract::CLIENT_ID_DEFAULT;
        $this->token_user_id = 1;
        $this->token_user_id_fake = 0;
        $this->token_ttl = 30;
    }

    /**
     * @return StorageContract|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getStorage()
    {
        return $this->createStorageMock();
    }

    /**
     * Create mock object for Storage
     * @return StorageContract|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createStorageMock()
    {
        $object = $this->createMock(StorageContract::class);
        return $object;
    }

    /**
     * Create default user object
     * @param int|null $id
     * @return User
     */
    protected function createUser($id = null)
    {
        $id = $id ?? $this->token_user_id;
        $user = new User([
            'id' => $id,
            'email' => 'example@example.com',
            'password' => Hash::make('no_password'),
        ]);
        return $user;
    }

    protected function createToken($ttl = false, $token = null, $user_id = null, $client_id = null)
    {
        $ttl = $ttl !== false ? $ttl : $this->token_ttl;
        $token = $token ?? $this->token_id;
        $user_id = $user_id ?? $this->token_user_id;
        $client_id = $client_id ?? $this->token_client_id;
        $data = [
            'user_id' => $user_id,
            'token' => $token,
            'client_id' => $client_id,
        ];
        $token = new AccessToken($data, $this->getStorage());
        $token->setTtl($ttl, true);
        return $token;
    }
}

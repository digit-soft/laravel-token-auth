<?php

namespace DigitSoft\LaravelTokenAuth\Tests;

use \DigitSoft\LaravelTokenAuth\Facades\TokenCached as AToken;
use DigitSoft\LaravelTokenAuth\AccessToken;
use DigitSoft\LaravelTokenAuth\Contracts\Storage as StorageContract;
use DigitSoft\LaravelTokenAuth\Guards\TokenGuard;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $token_id;

    protected $token_ttl;

    protected $token_user_id;

    protected $token_user_email;

    protected $token_user_password;

    protected $token_user_id_fake;

    protected $token_client_id;

    /**
     * @inheritdoc
     */
    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->token_id = '4BaoPSOuvasGj55BUJluikbbSC9eoaZk2Z3tI7kQB56hkp7xGNRQxfMfBMB0';
        $this->token_user_id = 1;
        $this->token_user_email = 'example@example.com';
        $this->token_user_password = 'no_password';
        $this->token_user_id_fake = 0;
        $this->token_ttl = 30;
    }

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->token_client_id = AToken::getDefaultClientId();
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
        $object->method('setToken')->willReturn(true);
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
            'email' => $this->token_user_email,
            'password' => Hash::make($this->token_user_password),
        ]);
        return $user;
    }

    /**
     * Create token object
     * @param bool        $ttl
     * @param string|null $token
     * @param int|null    $user_id
     * @param string|null $client_id
     * @param bool        $fromStorage
     * @return AccessToken
     */
    protected function createToken($ttl = false, $token = null, $user_id = null, $client_id = null, $fromStorage = false)
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
        $tokenObject = new AccessToken($this->getStorage(), $data, $fromStorage);
        $tokenObject->setTtl($ttl, true);
        if ($fromStorage) {
            $tokenObject->rememberState();
        }
        return $tokenObject;
    }

    /**
     * Create auth guard
     * @param Request|null      $request
     * @param UserProvider|null $userProvider
     * @return TokenGuard
     */
    protected function createGuard(Request $request = null, UserProvider $userProvider = null)
    {
        $userProvider = $userProvider ?? $this->createUserProvider();
        $request = $request ?? new Request();
        $guard = new TokenGuard($userProvider, $request);
        return $guard;
    }

    /**
     * Create user provider for guard
     * @return UserProvider
     */
    protected function createUserProvider()
    {
        $provider = new UserProvider($this->app['hash'], User::class);
        return $provider;
    }

    /**
     * Bind closure for storage resolving
     * @param \Closure $callback
     */
    protected function bindStorage(\Closure $callback)
    {
        $this->app->bind('auth-token.storage', $callback);
    }
}

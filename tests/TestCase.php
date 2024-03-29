<?php

namespace DigitSoft\LaravelTokenAuth\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use DigitSoft\LaravelTokenAuth\AccessToken;
use DigitSoft\LaravelTokenAuth\Guards\TokenGuard;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use DigitSoft\LaravelTokenAuth\Facades\TokenCached as AToken;
use DigitSoft\LaravelTokenAuth\Contracts\Storage as StorageContract;

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
        $this->token_id = 'oDBJX80udsxkxEh9AEnGe7IjtAvmK3B688dbbACe26155202a2D06A3f0c50BC73b0D72B9A8eB35Ebc50E8b1Ca3E9128LJt81Mjcp8gke2Iwowv5gQlJv564Fi';
        $this->token_user_id = 1;
        $this->token_user_email = 'example@example.com';
        $this->token_user_password = 'no_password';
        $this->token_user_id_fake = 0;
        $this->token_ttl = 30;
    }

    /**
     * @inheritdoc
     */
    protected function setUp(): void
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
    protected function createUser(?int $id = null)
    {
        $id = $id ?? $this->token_user_id;

        return new User([
            'id' => $id,
            'email' => $this->token_user_email,
            'password' => Hash::make($this->token_user_password),
        ]);
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
        $tokenObject = new AccessToken($data, $fromStorage);
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

        return new TokenGuard($userProvider, $request);
    }

    /**
     * Create user provider for guard
     * @return UserProvider
     */
    protected function createUserProvider()
    {
        return new UserProvider($this->app['hash'], User::class);
    }

    /**
     * Bind closure for storage resolving
     * @param \Closure $callback
     */
    protected function bindStorage(\Closure $callback)
    {
        $this->app->bind('auth-token.storage', $callback);
    }

    /**
     * Drop instance of TokenCached facade.
     *
     * @return void
     */
    protected function resetTokenCachedFacade(): void
    {
        app()->forgetInstance('auth-token');
        \DigitSoft\LaravelTokenAuth\Facades\TokenCached::clearResolvedInstance('auth-token');
    }
}

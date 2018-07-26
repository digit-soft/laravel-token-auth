<?php

namespace DigitSoft\LaravelTokenAuth\Tests\Unit;

use DigitSoft\LaravelTokenAuth\AccessToken;
use DigitSoft\LaravelTokenAuth\Storage\Redis;
use DigitSoft\LaravelTokenAuth\Tests\TestCase;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Class RedisStoreTest
 * @covers \DigitSoft\LaravelTokenAuth\Storage\Redis
 * @covers \DigitSoft\LaravelTokenAuth\Storage\StorageHelpers
 */
class RedisStoreTest extends TestCase
{
    protected $storage;

    protected $token_id;

    protected $token_ttl;

    protected $token_user_id;

    protected $token_client_id;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->token_id = '4BaoPSOuvasGj55BUJluikbbSC9eoaZk2Z3tI7kQB56hkp7xGNRQxfMfBMB0';
        $this->token_client_id = AccessToken::CLIENT_ID_DEFAULT;
        $this->token_user_id = 1;
        $this->token_ttl = 30;
    }

    /**
     * Connection success test
     * @coversNothing
     */
    public function testConnectionSuccess()
    {
        $connection = $this->getStorageConnection();
        $this->assertTrue($connection instanceof Connection);
        $this->assertNotNull($connection->keys('*'));
    }

    /**
     * Token insertion test
     */
    public function testTokenInsertAndRead()
    {
        $token = $this->createAuthToken();
        $this->getStorage()->setToken($token);
        $tokenRead = $this->getStorage()->getToken($token->token);
        $tokenExists = $this->getStorage()->tokenExists($token);
        $tokenReadEmpty = $this->getStorage()->getToken($token->token . "qwerty");
        $this->assertEmpty($tokenReadEmpty, 'False token not found');
        $this->assertInstanceOf(AccessToken::class, $tokenRead, 'Token instance of AccessToken');
        $this->assertTrue($tokenExists, 'Token exists in storage');
        $this->assertEquals($token->token, $tokenRead->token);
    }

    /**
     * Token insertion test
     */
    public function testTokenUserAssign()
    {
        $token = $this->createAuthToken();
        $this->getStorage()->setToken($token);
        $tokens = $this->getStorage()->getUserTokens($this->token_user_id);
        $tokensLoaded = $this->getStorage()->getUserTokens($this->token_user_id, true);
        $tokensEmpty = $this->getStorage()->getUserTokens(0);
        $this->assertTrue(isset($tokensLoaded[$token->token]), 'User tokens [loaded] contains given token');
        $this->assertNotEmpty($tokensLoaded, 'User tokens [loaded] not empty');
        $this->assertNotEmpty($tokens, 'User tokens not empty');
        $this->assertEmpty($tokensEmpty, 'Not existent user tokens are empty');
        $this->assertContains($token->token, $tokens, 'User tokens not empty');
    }

    public function testRemoveTokenFromUser()
    {
        $token = $this->createAuthToken();
        $this->getStorage()->setToken($token);
        $this->getStorage()->removeToken($token);
        $tokens = $this->getStorage()->getUserTokens($this->token_user_id, false);
        $this->getStorage()->setUserTokens($this->token_user_id, []);
        $tokensEmpty = $this->getStorage()->getUserTokens($this->token_user_id, false);
        $this->assertNotContains($token->token, $tokens, 'Token was removed from user assigns');
        $this->assertEmpty($tokensEmpty, 'User tokens assigns clear');
    }

    protected function getStorage()
    {
        if (!isset($this->storage)) {
            $this->storage = new Redis(config());
        }
        return $this->storage;
    }

    protected function getStorageConnection()
    {
        return $this->getStorage()->getConnection();
    }

    protected function createAuthToken($ttl = false, $token = null, $user_id = null, $client_id = null)
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
        $token = new AccessToken($data);
        $token->setTtl($ttl, true);
        return $token;
    }
}

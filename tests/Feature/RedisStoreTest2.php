<?php

namespace DigitSoft\LaravelTokenAuth\Tests\Feature;

use DigitSoft\LaravelTokenAuth\AccessToken;
use DigitSoft\LaravelTokenAuth\Storage\Redis;
use Illuminate\Contracts\Redis\Connection;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RedisStoreTest2 extends TestCase
{
    protected $storage;

    protected $token_id;

    protected $token_ttl;

    protected $token_user_id;

    protected $token_client_id;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->token_id = str_random(60);
        $this->token_client_id = AccessToken::CLIENT_ID_DEFAULT;
        $this->token_user_id = 1;
        $this->token_ttl = 30;
    }

    /**
     * Connection success test
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
        $this->assertTrue($tokenRead instanceof AccessToken);
        $this->assertEquals($token->token, $tokenRead->token);
    }

    protected function getStorage()
    {
        if (!isset($this->storage)) {
            echo  __METHOD__ . "\n";
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

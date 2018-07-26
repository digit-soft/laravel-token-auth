<?php

namespace DigitSoft\LaravelTokenAuth\Tests\Unit;

use DigitSoft\LaravelTokenAuth\AccessToken;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Tests\TestCase;
use DigitSoft\LaravelTokenAuth\Tests\User;

/**
 * Class AccessTokenTest
 * @package DigitSoft\LaravelTokenAuth\Tests\Unit
 * @covers \DigitSoft\LaravelTokenAuth\AccessToken
 * @covers \DigitSoft\LaravelTokenAuth\Events\AccessTokenCreated
 */
class AccessTokenTest extends TestCase
{
    protected $token_id;

    protected $token_ttl;

    protected $token_user_id;

    protected $token_client_id;

    /**
     * @inheritdoc
     */
    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->token_id = '4BaoPSOuvasGj55BUJluikbbSC9eoaZk2Z3tI7kQB56hkp7xGNRQxfMfBMB0';
        $this->token_client_id = AccessToken::CLIENT_ID_DEFAULT;
        $this->token_user_id = 1;
        $this->token_ttl = 30;
    }

    public function testCreateFromArray()
    {
        $data = [
            'token' => $this->token_id,
            'user_id' => $this->token_user_id,
            'ttl' => $this->token_ttl,
            'client_id' => $this->token_client_id,
        ];
        $token = AccessToken::createFromData($data);
        $this->assertEquals($data['token'], $token->token, 'Token ID is equal');
        $this->assertEquals($data['user_id'], $token->user_id, 'User ID is equal');
        $this->assertEquals($data['ttl'], $token->ttl, 'Time to live is equal');
        $this->assertEquals($data['client_id'], $token->client_id, 'Client ID is equal');
    }

    public function testCreateForUser()
    {
        $user = new User([
            'id' => $this->token_user_id,
            'email' => 'example@example.com',
            'password' => password_hash('no_password', PASSWORD_BCRYPT),
        ]);
        $token = AccessToken::createFor($user, $this->token_client_id);
        $this->assertEquals($user->getAuthIdentifier(), $token->user_id, 'User ID is equal');
        $this->assertNotEmpty($token->token, 'Token ID is not empty');
    }

    public function testSetDifferentTimeToLive()
    {
        $user = new User([
            'id' => $this->token_user_id,
            'email' => 'example@example.com',
            'password' => password_hash('no_password', PASSWORD_BCRYPT),
        ]);
        $token = AccessToken::createFor($user, $this->token_client_id, false);
        $this->assertNull($token->ttl, 'Time to live is NULL');
        $this->assertNull($token->iat, 'Issued at time is NULL');
        $this->assertNull($token->exp, 'Expire time is NULL');
        $this->assertFalse($token->isExpired(), 'Token is not expired');
        $token->setTtl($this->token_ttl);
        $exp = $token->iat + $token->ttl;
        $this->assertEquals($this->token_ttl, $token->ttl, 'TTL is equal');
        $this->assertNotNull($token->iat, 'Issued time is NOT NULL');
        $this->assertNotNull($token->exp, 'Expire time is NOT NULL');
        $this->assertEquals($exp, $token->exp, 'Expire time is equal');
        $token->setTtl(-1, true);
        $this->assertTrue($token->isExpired(), 'Token is expired');
    }

    public function testDumpToArray()
    {
        $token = $this->createToken();
        $tokenArray = $token->toArray();
        $this->assertTrue(is_array($tokenArray), 'Dump is an array');
        $this->assertArrayHasKey('token', $tokenArray, 'Dump has key token');
        $this->assertArrayHasKey('user_id', $tokenArray, 'Dump has key user_id');
        $this->assertArrayHasKey('client_id', $tokenArray, 'Dump has key client_id');
        $this->assertEquals($token->token, $tokenArray['token'], 'Token ID is equal');
        $this->assertEquals($token->user_id, $tokenArray['user_id'], 'User ID is equal');
        $this->assertEquals($token->client_id, $tokenArray['client_id'], 'Client ID is equal');
    }

    public function testDumpToJson()
    {
        $token = $this->createToken();
        $tokenJson = $token->toJson();
        $tokenArray = json_decode($tokenJson, true);
        $this->assertTrue(is_string($tokenJson), 'JSON is string');
        $this->assertTrue(is_array($tokenArray), 'JSON is valid');
    }

    public function testDumpToString()
    {
        $token = $this->createToken();
        $tokenStr1 = $token->__toString();
        $tokenStr2 = (string)$token;
        $this->assertTrue(is_string($tokenStr1), 'Token ID is string');
        $this->assertEquals($token->token, $tokenStr1, 'Token IDs are equal');
        $this->assertEquals($tokenStr1, $tokenStr2, 'Strings are equal');
    }

    public function testSaveToStorage()
    {
        $token = $this->createToken(null);
        $token->iat = null;
        $token->save();
        $getStorage = $this->getNotAccessibleMethod($token, 'getTokensStorage');
        /** @var Storage $storage */
        $storage = $getStorage->invoke($token);
        $this->assertInstanceOf(Storage::class, $storage);
        $tokenRead = $storage->getToken($token->token);
        $this->assertInstanceOf(AccessToken::class, $tokenRead, 'Token found');
        $this->assertEquals($tokenRead->token, $token->token, 'Found equal token');
        $token->remove();
        $tokenReadEmpty = $storage->getToken($token->token);
        $this->assertNull($tokenReadEmpty, 'Token removed');
        $storage->setUserTokens($token->user_id, []);
        $user = new User([
            'id' => $this->token_user_id,
            'email' => 'example@example.com',
            'password' => password_hash('no_password', PASSWORD_BCRYPT),
        ]);
        $tokenNew = $this->createToken();
        $tokenNew->save();
        $tokenReadNew = AccessToken::getFirstFor($user);
        $tokenReadNew2 = AccessToken::getFirstFor($user, 'api2');
        $this->assertInstanceOf(AccessToken::class, $tokenReadNew, 'Token found');
        $this->assertEquals($tokenReadNew->token, $tokenNew->token, 'Found equal token');
        $this->assertNull($tokenReadNew2, 'Token for client api2 not found');
        $tokenNew->remove();
        $tokenReadNewEmpty = AccessToken::getFirstFor($user);
        $this->assertNull($tokenReadNewEmpty, 'Token not found');
    }

    /**
     * Create access token
     * @param bool $ttl
     * @param null $token
     * @param null $user_id
     * @param null $client_id
     * @return AccessToken|null|string
     */
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
        $token = new AccessToken($data);
        $token->setTtl($ttl, true);
        return $token;
    }

    protected function getNotAccessibleMethod($object, $methodName)
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }
}
<?php

namespace DigitSoft\LaravelTokenAuth\Tests\Unit;

use DigitSoft\LaravelTokenAuth\AccessToken;
use Illuminate\Redis\Connections\Connection;
use DigitSoft\LaravelTokenAuth\Storage\Redis;
use DigitSoft\LaravelTokenAuth\Tests\TestCase;
use DigitSoft\LaravelTokenAuth\Facades\TokenCached;

/**
 * Class RedisStoreTest
 * @covers \DigitSoft\LaravelTokenAuth\Storage\Redis
 * @covers \DigitSoft\LaravelTokenAuth\Storage\StorageHelpers
 */
class RedisStoreTest extends TestCase
{
    protected $storage;

    /**
     * Connection success test
     * @coversNothing
     */
    public function testConnectionSuccess()
    {
        $connection = $this->getStorageConnection();
        static::assertInstanceOf(Connection::class, $connection);
        static::assertNotNull($connection->keys('*'));
    }

    /**
     * Token insertion test
     */
    public function testTokenInsertAndRead()
    {
        $token = $this->createToken();
        $tokenNoTtl = $this->createToken(null, TokenCached::generateTokenStr());
        $tokenNoUser = $this->createToken(10, TokenCached::generateTokenStr());
        $tokenNoUser->user_id = \DigitSoft\LaravelTokenAuth\Contracts\AccessToken::USER_ID_GUEST;
        $this->getStorage()->setManager(app('redis'));
        $this->getStorage()->setToken($token);
        $this->getStorage()->setToken($tokenNoTtl);
        $this->getStorage()->setToken($tokenNoUser);
        $tokenRead = $this->getStorage()->getToken($token->token);
        $tokenNoTtlRead = $this->getStorage()->getToken($tokenNoTtl->token);
        $tokenNoUserRead = $this->getStorage()->getToken($tokenNoUser->token);
        $tokenExists = $this->getStorage()->tokenExists($token);
        $tokenReadEmpty = $this->getStorage()->getToken($token->token . "qwerty");
        $tokenReadMultipleEmpty = $this->getStorage()->getTokens([]);
        static::assertEmpty($tokenReadEmpty, 'False token not found');
        static::assertEmpty($tokenReadMultipleEmpty, 'Empty array passed to ::getTokens()');
        static::assertInstanceOf(AccessToken::class, $tokenRead, 'Token is an instance of AccessToken');
        static::assertInstanceOf(AccessToken::class, $tokenNoTtlRead, 'Token (without TTL) is an instance of AccessToken');
        static::assertInstanceOf(AccessToken::class, $tokenNoUserRead, 'Token (without user) is an instance of AccessToken');
        static::assertTrue($tokenExists, 'Token exists in storage');
        static::assertEquals($token->token, $tokenRead->token, 'Token read successfully');
        static::assertEquals($tokenNoTtl->token, $tokenNoTtlRead->token, 'Token without TTL read successfully');
        static::assertEquals($tokenNoUser->token, $tokenNoUserRead->token, 'Token without user read successfully');
        static::assertNull($tokenNoTtl->ttl, 'TTL in token is null');
        static::assertTrue($tokenNoUserRead->isGuest(), 'Token without user is valid guest token');
        $this->getStorage()->removeToken($tokenNoTtl);
    }

    /**
     * Token insertion test
     */
    public function testTokenUserAssign()
    {
        $token = $this->createToken();
        $tokenExpired = $this->createToken(0, TokenCached::generateTokenStr());
        $tokenExpiring = $this->createToken(1, TokenCached::generateTokenStr());
        $this->getStorage()->setToken($token);
        $this->getStorage()->setToken($tokenExpired);
        $this->getStorage()->setToken($tokenExpiring);
        sleep(2);
        $tokens = $this->getStorage()->getUserTokens($this->token_user_id);
        $tokensByIds = $this->getStorage()->getTokens([$token->token, $tokenExpired->token, $tokenExpiring->token]);
        $tokensLoaded = $this->getStorage()->getUserTokens($this->token_user_id, true);
        $tokensEmpty = $this->getStorage()->getUserTokens(0);
        static::assertTrue(isset($tokensLoaded[$token->token]), 'User tokens [loaded] contains given token');
        static::assertFalse(isset($tokensLoaded[$tokenExpiring->token]), 'User tokens [loaded] does not contain expiring token');
        static::assertNotEmpty($tokensLoaded, 'User tokens [loaded] not empty');
        static::assertNotEmpty($tokens, 'User tokens not empty');
        static::assertEmpty($tokensEmpty, 'Not existent user tokens are empty');
        static::assertContains($token->token, $tokens, 'User tokens not empty');
        static::assertNotContains($tokenExpired->token, $tokens, 'User tokens not contain expired token');
        static::assertNotContains($tokenExpired->token, $tokensByIds, 'Tokens got by IDs does not contain expired token');
        static::assertNotContains($tokenExpiring->token, $tokensByIds, 'Tokens got by IDs does not contain expiring token');
    }

    public function testRemoveTokenFromUser()
    {
        $token = $this->createToken();
        $this->getStorage()->setToken($token);
        $this->getStorage()->removeToken($token);
        $tokens = $this->getStorage()->getUserTokens($this->token_user_id, false);
        $this->getStorage()->setUserTokens($this->token_user_id, []);
        $tokensEmpty = $this->getStorage()->getUserTokens($this->token_user_id, false);
        static::assertNotContains($token->token, $tokens, 'Token was removed from user assigns');
        static::assertEmpty($tokensEmpty, 'User tokens assigns clear');
    }

    public function testUserMassiveTokenAssign()
    {
        $storage = $this->getStorage();
        app()->instance('auth-token.storage', $storage);

        /** @var \DigitSoft\LaravelTokenAuth\Contracts\AccessToken[] $tokens */
        $tokens = [];
        $tokens[] = $this->createToken(false, TokenCached::generateTokenStr());
        $tokens[] = $this->createToken(false, TokenCached::generateTokenStr());
        $tokens[] = $this->createToken(false, TokenCached::generateTokenStr());
        foreach ($tokens as $token) {
            $token->save();
        }
        $tokensFirstRead = $storage->getUserTokens($this->token_user_id);
        static::assertNotEmpty($tokensFirstRead, 'First user tokens list read success');
        $storage->setUserTokens($this->token_user_id, $tokens);
        $tokensSecondRead = $storage->getUserTokens($this->token_user_id);
        static::assertNotEmpty($tokensSecondRead, 'Second user tokens list read success');
        static::assertEquals($tokensFirstRead, $tokensSecondRead, 'First data and second data are equals');
    }

    protected function getStorage()
    {
        if (! isset($this->storage)) {
            $this->storage = new Redis(config(), $this->app->get('redis'));
        }

        return $this->storage;
    }

    protected function getStorageConnection()
    {
        return $this->getStorage()->getConnection();
    }
}

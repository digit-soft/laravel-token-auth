<?php

namespace DigitSoft\LaravelTokenAuth\Tests\Unit;

use DigitSoft\LaravelTokenAuth\AccessToken;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Tests\TestCase;
use DigitSoft\LaravelTokenAuth\Tests\User;

/**
 * Class HasTokensTraitTest
 * @package DigitSoft\LaravelTokenAuth\Tests\Unit
 * @covers \DigitSoft\LaravelTokenAuth\Eloquent\HasTokens
 */
class HasTokensTraitTest extends TestCase
{

    protected $token_id;

    protected $token_ttl;

    protected $token_user_id;

    protected $token_user_id_fake;

    protected $token_client_id;

    public function testGetUserTokens()
    {
        $storage = $this->createStorageMock();
        $storage->expects($this->at(0))
            ->method('getUserTokens')
            ->willReturn([]);
        $storage->expects($this->at(1))
            ->method('getUserTokens')
            ->willReturn([$this->createToken()]);
        $this->app->bind('auth.tokencached.storage', function () use ($storage) { return $storage; });
        $user = $this->createUser();
        $tokensEmpty = $user->getTokens();
        $tokensNotEmpty = $user->getTokens();
        $this->assertEmpty($tokensEmpty, 'No tokens found for user');
        $this->assertNotEmpty($tokensNotEmpty, 'Tokens found for user');
    }

    public function testGetUserTokenForClient()
    {
        $storage = $this->createStorageMock();
        $storage->expects($this->at(0))
            ->method('getUserTokens')
            ->willReturn([$this->createToken()]);
        $this->app->bind('auth.tokencached.storage', function () use ($storage) { return $storage; });
        $user = $this->createUser();
        $tokenFound = $user->getToken($this->token_client_id);
        $this->assertInstanceOf(AccessTokenContract::class, $tokenFound, 'Found token for user');
    }

    public function testCreateUserToken()
    {
        $storage = $this->createStorageMock();
        $this->app->bind('auth.tokencached.storage', function () use ($storage) { return $storage; });
        $user = $this->createUser();
        $tokenCreated = $user->createToken($this->token_client_id, 99);
        $this->assertInstanceOf(AccessTokenContract::class, $tokenCreated, 'Token class created token for user');
    }

    /**
     * @return Storage|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createStorageMock()
    {
        $object = parent::createStorageMock();
        $object->expects($this->any())
            ->method('tokenExists')
            ->willReturnOnConsecutiveCalls(true, false);
        return $object;
    }
}
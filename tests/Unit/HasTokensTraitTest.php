<?php

namespace DigitSoft\LaravelTokenAuth\Tests\Unit;

use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Tests\TestCase;

/**
 * Class HasTokensTraitTest
 * @package DigitSoft\LaravelTokenAuth\Tests\Unit
 * @covers \DigitSoft\LaravelTokenAuth\Eloquent\HasTokens
 */
class HasTokensTraitTest extends TestCase
{
    public function testGetUserTokens()
    {
        $storage = $this->createStorageMock();
        $storage->method('getUserTokens')
            ->willReturnOnConsecutiveCalls([], [$this->createToken()]);
        $this->bindStorage(function () use ($storage) { return $storage; });
        $user = $this->createUser();
        $tokensEmpty = $user->getTokens();
        $tokensNotEmpty = $user->getTokens();
        static::assertEmpty($tokensEmpty, 'No tokens found for user');
        static::assertNotEmpty($tokensNotEmpty, 'Tokens found for user');
    }

    public function testGetUserTokenForClient()
    {
        $storage = $this->createStorageMock();
        $storage->method('getUserTokens')
            ->willReturnOnConsecutiveCalls([$this->createToken()], []);
        $this->bindStorage(function () use ($storage) { return $storage; });
        $user = $this->createUser();
        $tokenFound = $user->getToken($this->token_client_id);
        // Assume that $this->token_client_id is DEFAULT CLIENT ID
        $tokenFoundFromRequest = $user->getToken();
        static::assertInstanceOf(AccessTokenContract::class, $tokenFound, 'Found token for user');
        static::assertInstanceOf(AccessTokenContract::class, $tokenFoundFromRequest, 'Found token for user from request');
    }

    public function testCreateUserToken()
    {
        $storage = $this->createStorageMock();
        $this->bindStorage(function () use ($storage) { return $storage; });
        $user = $this->createUser();
        $tokenCreated = $user->createToken($this->token_client_id, 99);
        static::assertInstanceOf(AccessTokenContract::class, $tokenCreated, 'Token class created token for user');
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

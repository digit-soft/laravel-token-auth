<?php

namespace DigitSoft\LaravelTokenAuth\Tests\Unit;

use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Facades\TokenCached;
use DigitSoft\LaravelTokenAuth\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * Class AccessTokenTest
 * @package DigitSoft\LaravelTokenAuth\Tests\Unit
 * @covers \DigitSoft\LaravelTokenAuth\AccessToken
 * @covers \DigitSoft\LaravelTokenAuth\Events\AccessTokenCreated
 * @covers \DigitSoft\LaravelTokenAuth\AccessTokenHelper
 * @covers \DigitSoft\LaravelTokenAuth\Traits\TracksPropertiesChanges
 */
class AccessTokenTest extends TestCase
{
    public function testCreateFromArray()
    {
        $data = [
            'token' => $this->token_id,
            'user_id' => $this->token_user_id,
            'ttl' => $this->token_ttl,
            'client_id' => $this->token_client_id,
        ];
        $token = TokenCached::createFromData($data);
        static::assertEquals($data['token'], $token->token, 'Token ID is equal');
        static::assertEquals($data['user_id'], $token->user_id, 'User ID is equal');
        static::assertEquals($data['ttl'], $token->ttl, 'Time to live is equal');
        static::assertEquals($data['client_id'], $token->client_id, 'Client ID is equal');
    }

    public function testCreateForUser()
    {
        $user = $this->createUser();
        $this->bindStorage(function () { return $this->createStorageMock(); });
        $token = TokenCached::createFor($user);
        static::assertEquals($user->getAuthIdentifier(), $token->user_id, 'User ID is equal');
        static::assertNotEmpty($token->token, 'Token ID is not empty');
    }

    public function testSetDifferentTimeToLive()
    {
        $user = $this->createUser();
        $token = TokenCached::createFor($user, $this->token_client_id, false);
        static::assertNull($token->ttl, 'Time to live is NULL');
        static::assertNull($token->iat, 'Issued at time is NULL');
        static::assertNull($token->exp, 'Expire time is NULL');
        static::assertFalse($token->isExpired(), 'Token is not expired');
        $token->setTtl($this->token_ttl);
        $exp = $token->iat + $token->ttl;
        static::assertEquals($this->token_ttl, $token->ttl, 'TTL is equal');
        static::assertNotNull($token->iat, 'Issued time is NOT NULL');
        static::assertNotNull($token->exp, 'Expire time is NOT NULL');
        static::assertEquals($exp, $token->exp, 'Expire time is equal');
        $token->setTtl(-1, true);
        static::assertTrue($token->isExpired(), 'Token is expired');
    }

    public function testDumpToArray()
    {
        $token = $this->createToken();
        $tokenArray = $token->toArray();
        static::assertIsArray($tokenArray, 'Dump is an array');
        static::assertArrayHasKey('token', $tokenArray, 'Dump has key token');
        static::assertArrayHasKey('user_id', $tokenArray, 'Dump has key user_id');
        static::assertArrayHasKey('client_id', $tokenArray, 'Dump has key client_id');
        static::assertEquals($token->token, $tokenArray['token'], 'Token ID is equal');
        static::assertEquals($token->user_id, $tokenArray['user_id'], 'User ID is equal');
        static::assertEquals($token->client_id, $tokenArray['client_id'], 'Client ID is equal');
    }

    public function testDumpToJson()
    {
        $token = $this->createToken();
        $tokenJson = $token->toJson();
        $tokenArray = json_decode($tokenJson, true);
        static::assertIsString($tokenJson, 'JSON is string');
        static::assertIsArray($tokenArray, 'JSON is valid');
    }

    public function testDumpToString()
    {
        $token = $this->createToken();
        $tokenStr1 = $token->__toString();
        $tokenStr2 = (string)$token;
        static::assertIsString($tokenStr1, 'Token ID is string');
        static::assertEquals($token->token, $tokenStr1, 'Token IDs are equal');
        static::assertEquals($tokenStr1, $tokenStr2, 'Strings are equal');
    }

    // public function testSettingStorageToTokenObject()
    // {
    //     $token = $this->createToken();
    //     $newStorage = $this->createStorageMock();
    //     static::assertNotSame($newStorage, $token->getStorage(), 'Storages are not equal');
    //     $token->setStorage($newStorage);
    //     static::assertSame($newStorage, $token->getStorage(), 'Storages are equal');
    // }

    public function testSaveTokenToStorage()
    {
        $storage = $this->getStorage();
        app()->instance('auth-token.storage', $storage);

        $token = $this->createToken(null);
        $this->configureStorageMockForTokenSave($storage);
        $this->configureStorageMockForTokenGet($storage, $token);
        $token->iat = null;
        $token->save();
        $tokenRead = $storage->getToken($token->token);
        static::assertInstanceOf(AccessTokenContract::class, $tokenRead, 'Token found');
        static::assertEquals($tokenRead->token, $token->token, 'Found equal token');
    }

    public function testRegenerateToken()
    {
        $storage = $this->getStorage();
        app()->instance('auth-token.storage', $storage);

        $token = $this->createToken(null);
        $this->configureStorageMockForTokenSave($storage);
        $oldToken = $token->token;
        $token->regenerate(true);
        $newToken = $token->token;
        //$token->save();
        $this->configureStorageMockForTokenGet($storage, $token);
        $tokenRead = $storage->getToken($token->token);
        static::assertInstanceOf(AccessTokenContract::class, $tokenRead, 'Token found');
        static::assertEquals($tokenRead->token, $token->token, 'Found equal token');
        static::assertNotEquals($oldToken, $newToken, 'Regenerated token is different');
    }

    public function testGetClientIdFromRequest()
    {
        $clientId = 'api-test';
        $clientIdFake = 'api-test-2';
        config()->set('auth-token.client_ids', ['api-test']);
        $requestGet = new Request([AccessTokenContract::REQUEST_CLIENT_ID_PARAM => $clientId]);
        $requestPost = new Request([], [AccessTokenContract::REQUEST_CLIENT_ID_PARAM => $clientId]);
        $requestPost->setMethod(Request::METHOD_POST);
        $requestHeader = new Request();
        $requestHeader->headers->set(AccessTokenContract::REQUEST_CLIENT_ID_HEADER, $clientId);
        $requestEmpty = new Request();
        $this->resetTokenCachedFacade();
        static::assertEquals($clientId, TokenCached::getClientIdFromRequest($requestGet), 'Get client id from GET params');
        static::assertEquals($clientId, TokenCached::getClientIdFromRequest($requestPost), 'Get client id from POST params');
        static::assertEquals($clientId, TokenCached::getClientIdFromRequest($requestHeader), 'Get client id from headers');
        static::assertEquals(TokenCached::getDefaultClientId(), TokenCached::getClientIdFromRequest($requestEmpty), 'Get client id from empty request');
        static::assertNotEquals($clientIdFake, TokenCached::getClientIdFromRequest($requestGet));
    }

    public function testGuestTokenGeneration()
    {
        $user = $this->createUser();
        $tokenUser = TokenCached::createFor($user);
        $tokenGuest = TokenCached::createForGuest();
        static::assertFalse($tokenUser->isGuest(), 'Token for user is checking correct');
        static::assertTrue($tokenGuest->isGuest(), 'Token for guest is checking correct');
        static::assertNotEmpty($tokenGuest->token, 'Token for guest is not empty');
        static::assertNotNull($tokenGuest->ttl, 'Token for guest has not empty TTL');
    }

    public function testGetFirstUserTokenAfterSave()
    {
        $storage = $this->getStorage();
        app()->instance('auth-token.storage', $storage);

        $token = $this->createToken(null);
        $this->configureStorageMockForTokenSave($storage);
        $this->configureStorageMockForTokenGetByUser($storage, $token);
        $token->save();
        $user = $this->createUser();
        $user2 = $this->createUser($this->token_user_id_fake);
        $this->bindStorage(function () use ($token, $storage) { return $storage; });
        $tokenRead = TokenCached::getFirstFor($user);
        $tokenReadEmpty = TokenCached::getFirstFor($user, $this->token_client_id . '2');
        $tokenReadEmpty2 = TokenCached::getFirstFor($user2, $this->token_client_id);
        static::assertInstanceOf(AccessTokenContract::class, $tokenRead, 'Token found');
        static::assertEquals($tokenRead->token, $token->token, 'Found equal token');
        static::assertNull($tokenReadEmpty, 'Token for non existing client not found');
        static::assertNull($tokenReadEmpty2, 'Token for non existing user not found');
    }

    public function testUpdateTokenPropertiesAndCheckState()
    {
        $token = $this->createToken(30, null, null, null, true);
        $tokenNoState = $this->createToken(30, TokenCached::generateTokenStr());
        static::assertFalse($token->isChanged(), 'Token was not changed');
        $oldTokenId = $token->token;
        $token->regenerate();
        static::assertTrue($token->isChanged(), 'Token was changed');
        static::assertNotEquals($oldTokenId, $token->token, 'Token ID was changed');
        $token->restoreState();
        static::assertEquals($oldTokenId, $token->token, 'Token ID was restored');
        static::assertTrue($tokenNoState->isChanged(), 'Token has changes because it has no state');
        $tokenNoStateData = $tokenNoState->toArray(true);
        $tokenNoState->restoreState();
        static::assertEquals($tokenNoState->toArray(true), $tokenNoStateData, 'Token has empty state so cant be restored');
    }

    public function testRemoveTokenFromStorage()
    {
        $storage = $this->getStorage();
        app()->instance('auth-token.storage', $storage);

        $token = $this->createToken(null);
        $this->configureStorageMockForTokenSave($storage);
        $this->configureStorageMockForTokenRemove($storage, $token);
        $token->save();
        $token->remove();
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

    /**
     * @param Storage|\PHPUnit\Framework\MockObject\MockObject $mock
     */
    protected function configureStorageMockForTokenSave($mock)
    {
        $mock->expects($this->once())
            ->method('setToken');
    }

    /**
     * @param Storage|\PHPUnit\Framework\MockObject\MockObject $mock
     * @param AccessTokenContract $token
     */
    protected function configureStorageMockForTokenRemove($mock, $token)
    {
        $mock->expects($this->once())
            ->method('removeToken')
            ->with($token);
    }

    /**
     * @param Storage|\PHPUnit\Framework\MockObject\MockObject $mock
     * @param AccessTokenContract $token
     */
    protected function configureStorageMockForTokenGet($mock, $token)
    {
        $mock
            ->expects($this->once())
            ->method('getToken')
            ->with($token->token)
            ->willReturn($token);
    }

    /**
     * @param Storage|\PHPUnit\Framework\MockObject\MockObject $mock
     * @param AccessTokenContract $token
     */
    protected function configureStorageMockForTokenGetByUser($mock, $token)
    {
        // Three calls for existing client id, not existing client id and not existing user
        $tokenToReturn = TokenCached::createFromData($token->toArray(true), true);
        $mock->expects($this->atLeast(3))
            ->method('getUserTokens')
            ->withConsecutive(
                [$this->token_user_id, true],
                [$this->token_user_id, true],
                [$this->token_user_id_fake, true]
            )
            ->willReturnOnConsecutiveCalls(
                [$token->token => $tokenToReturn],
                [$token->token => $tokenToReturn],
                []
            );
    }
}

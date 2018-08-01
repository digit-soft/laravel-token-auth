<?php

namespace DigitSoft\LaravelTokenAuth\Tests\Unit;

use DigitSoft\LaravelTokenAuth\Facades\TokenCached as AToken;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * Class AccessTokenTest
 * @package DigitSoft\LaravelTokenAuth\Tests\Unit
 * @covers \DigitSoft\LaravelTokenAuth\AccessToken
 * @covers \DigitSoft\LaravelTokenAuth\Events\AccessTokenCreated
 * @covers \DigitSoft\LaravelTokenAuth\AccessTokenHelper
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
        $token = AToken::createFromData($data);
        $this->assertEquals($data['token'], $token->token, 'Token ID is equal');
        $this->assertEquals($data['user_id'], $token->user_id, 'User ID is equal');
        $this->assertEquals($data['ttl'], $token->ttl, 'Time to live is equal');
        $this->assertEquals($data['client_id'], $token->client_id, 'Client ID is equal');
    }

    public function testCreateForUser()
    {
        $user = $this->createUser();
        $this->bindStorage(function () { return $this->createStorageMock(); });
        $token = AToken::createFor($user);
        $this->assertEquals($user->getAuthIdentifier(), $token->user_id, 'User ID is equal');
        $this->assertNotEmpty($token->token, 'Token ID is not empty');
    }

    public function testSetDifferentTimeToLive()
    {
        $user = $this->createUser();
        $token = AToken::createFor($user, $this->token_client_id, false);
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

    public function testSettingStorageToTokenObject()
    {
        $token = $this->createToken();
        $newStorage = $this->createStorageMock();
        $this->assertNotSame($newStorage, $token->getStorage(), 'Storages are not equal');
        $token->setStorage($newStorage);
        $this->assertSame($newStorage, $token->getStorage(), 'Storages are equal');
    }

    public function testSaveTokenToStorage()
    {
        $token = $this->createToken(null);
        $this->configureStorageMockForTokenSave($token->getStorage());
        $this->configureStorageMockForTokenGet($token->getStorage(), $token);
        $token->iat = null;
        $token->save();
        $tokenRead = $token->getStorage()->getToken($token->token);
        $this->assertInstanceOf(AccessTokenContract::class, $tokenRead, 'Token found');
        $this->assertEquals($tokenRead->token, $token->token, 'Found equal token');
    }

    public function testRegenerateToken()
    {
        $token = $this->createToken(null);
        $this->configureStorageMockForTokenSave($token->getStorage());
        $oldToken = $token->token;
        $token->regenerate(true);
        $newToken = $token->token;
        //$token->save();
        $this->configureStorageMockForTokenGet($token->getStorage(), $token);
        $tokenRead = $token->getStorage()->getToken($token->token);
        $this->assertInstanceOf(AccessTokenContract::class, $tokenRead, 'Token found');
        $this->assertEquals($tokenRead->token, $token->token, 'Found equal token');
        $this->assertNotEquals($oldToken, $newToken, 'Regenerated token is different');
    }

    public function testGetClientIdFromRequest()
    {
        $clientId = 'api-test';
        $clientIdFake = 'api-test-2';
        config(['auth-token.client_ids' => ['api-test']]);
        $requestGet = new Request([AccessTokenContract::REQUEST_CLIENT_ID_PARAM => $clientId]);
        $requestPost = new Request([], [AccessTokenContract::REQUEST_CLIENT_ID_PARAM => $clientId]);
        $requestPost->setMethod(Request::METHOD_POST);
        $requestHeader = new Request();
        $requestHeader->headers->set(AccessTokenContract::REQUEST_CLIENT_ID_HEADER, $clientId);
        $requestEmpty = new Request();
        $this->assertEquals($clientId, AToken::getClientIdFromRequest($requestGet), 'Get client id from GET params');
        $this->assertEquals($clientId, AToken::getClientIdFromRequest($requestPost), 'Get client id from POST params');
        $this->assertEquals($clientId, AToken::getClientIdFromRequest($requestHeader), 'Get client id from headers');
        $this->assertEquals(AToken::getDefaultClientId(), AToken::getClientIdFromRequest($requestEmpty), 'Get client id from empty request');
        $this->assertNotEquals($clientIdFake, AToken::getClientIdFromRequest($requestGet));
    }

    public function testGuestTokenGeneration()
    {
        $user = $this->createUser();
        $tokenUser = AToken::createFor($user);
        $tokenGuest = AToken::createForGuest();
        $this->assertFalse($tokenUser->isGuest(), 'Token for user is checking correct');
        $this->assertTrue($tokenGuest->isGuest(), 'Token for guest is checking correct');
        $this->assertNotEmpty($tokenGuest->token, 'Token for guest is not empty');
        $this->assertNotNull($tokenGuest->ttl, 'Token for guest has not empty TTL');
    }

    public function testGetFirstUserTokenAfterSave()
    {
        $token = $this->createToken(null);
        $this->configureStorageMockForTokenSave($token->getStorage());
        $this->configureStorageMockForTokenGetByUser($token->getStorage(), $token);
        $token->save();
        $user = $this->createUser();
        $user2 = $this->createUser($this->token_user_id_fake);
        $this->bindStorage(function () use ($token) { return $token->getStorage(); });
        $tokenRead = AToken::getFirstFor($user);
        $tokenReadEmpty = AToken::getFirstFor($user, $this->token_client_id . '2');
        $tokenReadEmpty2 = AToken::getFirstFor($user2, $this->token_client_id);
        $this->assertInstanceOf(AccessTokenContract::class, $tokenRead, 'Token found');
        $this->assertEquals($tokenRead->token, $token->token, 'Found equal token');
        $this->assertNull($tokenReadEmpty, 'Token for non existing client not found');
        $this->assertNull($tokenReadEmpty2, 'Token for non existing user not found');
    }

    public function testRemoveTokenFromStorage()
    {
        $token = $this->createToken(null);
        $this->configureStorageMockForTokenSave($token->getStorage());
        $this->configureStorageMockForTokenRemove($token->getStorage(), $token);
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
        $mock->expects($this->atLeast(3))
            ->method('getUserTokens')
            ->withConsecutive(
                [$this->token_user_id, true],
                [$this->token_user_id, true],
                [$this->token_user_id_fake, true]
            )
            ->willReturnOnConsecutiveCalls(
                [$token->token => $token],
                [$token->token => $token],
                []
            );
    }
}
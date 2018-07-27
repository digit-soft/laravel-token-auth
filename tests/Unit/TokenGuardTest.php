<?php

namespace DigitSoft\LaravelTokenAuth\Tests\Unit;

use DigitSoft\LaravelTokenAuth\Tests\TestCase;
use DigitSoft\LaravelTokenAuth\Tests\User;
use Illuminate\Http\Request;

/**
 * Class TokenGuardTest
 * @package DigitSoft\LaravelTokenAuth\Tests\Unit
 * @covers \DigitSoft\LaravelTokenAuth\Guards\TokenGuard
 */
class TokenGuardTest extends TestCase
{
    public function testSuccessAuthByGetParam()
    {
        list($usersProvider, $user, $storage) = $this->getAuthObjects();
        $request = new Request(['api_token' => $this->token_id]);
        $guard = $this->createGuard($request, $usersProvider);
        $this->assertTrue($guard->check(), 'Auth success by GET param');
        $this->assertInstanceOf(User::class, $guard->user(), 'Got valid user object');
    }

    public function testSuccessAuthByPostParam()
    {
        list($usersProvider, $user, $storage) = $this->getAuthObjects();
        $request = new Request([], ['api_token' => $this->token_id]);
        $request->setMethod(Request::METHOD_POST);
        $guard = $this->createGuard($request, $usersProvider);
        $this->assertTrue($guard->check(), 'Auth success by POST param');
        $this->assertInstanceOf(User::class, $guard->user(), 'Got valid user object');
    }

    public function testSuccessAuthByBearerTokenHeader()
    {
        list($usersProvider, $user, $storage) = $this->getAuthObjects();
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer ' . $this->token_id);
        $guard = $this->createGuard($request, $usersProvider);
        $this->assertTrue($guard->check(), 'Auth success by Bearer token header');
        $this->assertInstanceOf(User::class, $guard->user(), 'Got valid user object');
    }

    public function testSuccessAuthByPhpAuthHeader()
    {
        list($usersProvider, $user, $storage) = $this->getAuthObjects();
        $request = new Request();
        $request->headers->set('PHP_AUTH_PW', $this->token_id);
        $guard = $this->createGuard($request, $usersProvider);
        $this->assertTrue($guard->check(), 'Auth success by Bearer token header');
        $this->assertInstanceOf(User::class, $guard->user(), 'Got valid user object');
    }

    public function testAuthFailOnInvalidCredentials()
    {
        $falseTokenStr = $this->token_id . str_random(5);
        list($usersProvider, $user, $storage) = $this->getAuthObjects();
        $requestGet = new Request(['api_token' => $falseTokenStr]);
        $requestPost = new Request([], ['api_token' => $falseTokenStr]);
        $requestPost->setMethod(Request::METHOD_POST);
        $requestBearer = new Request();
        $requestBearer->headers->set('Authorization', 'Bearer ' . $falseTokenStr);
        $requestPhpAuth = new Request();
        $requestPhpAuth->headers->set('PHP_AUTH_PW', $falseTokenStr);
        $guardGet = $this->createGuard($requestGet, $usersProvider);
        $guardPost = $this->createGuard($requestPost, $usersProvider);
        $guardBearer = $this->createGuard($requestBearer, $usersProvider);
        $guardPhpAuth = $this->createGuard($requestPhpAuth, $usersProvider);
        $this->assertFalse($guardGet->check(), 'Auth failed with invalid token in GET array');
        $this->assertFalse($guardPost->check(), 'Auth failed with invalid token in POST array');
        $this->assertFalse($guardBearer->check(), 'Auth failed with invalid token in Bearer header');
        $this->assertFalse($guardPhpAuth->check(), 'Auth failed with invalid token in PHP_AUTH_PW header');
    }

    public function testCheckAuthUserStatusFunctions()
    {
        list($usersProvider, $user, $storage) = $this->getAuthObjects();
        $request = new Request(['api_token' => $this->token_id]);
        $guard = $this->createGuard($request, $usersProvider);
        $this->assertTrue($guard->check(), 'User is authorized');
        $this->assertFalse($guard->guest(), 'User is not a guest');
        $this->assertEquals($guard->id(), $this->token_user_id, 'User ID is correct');
    }

    public function testReturnValidUserObject()
    {
        list($usersProvider, $user, $storage) = $this->getAuthObjects();
        $request = new Request(['api_token' => $this->token_id]);
        $guard = $this->createGuard($request, $usersProvider);
        $this->assertInstanceOf(User::class, $guard->user(), 'Returned valid user class');
        $this->assertInstanceOf(User::class, $guard->authenticate(), 'Returned valid user class by ::authenticate() method');
        $this->assertTrue($guard->hasUser(), 'Has user object');
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException
     * @expectedException \Illuminate\Auth\AuthenticationException
     */
    public function testThrowAnExceptionOnInvalidTokenByAuthenticateMethod()
    {
        $falseTokenStr = $this->token_id . str_random(5);
        list($usersProvider, $user, $storage) = $this->getAuthObjects();
        $request = new Request(['api_token' => $falseTokenStr]);
        $guard = $this->createGuard($request, $usersProvider);
        $guard->authenticate();
    }

    public function testResetAuthStatusOnNewRequest()
    {
        $falseTokenStr = $this->token_id . str_random(5);
        list($usersProvider, $user, $storage) = $this->getAuthObjects();
        $request = new Request(['api_token' => $this->token_id]);
        $requestNew = new Request(['api_token' => $falseTokenStr]);
        $guard = $this->createGuard($request, $usersProvider);
        $this->assertTrue($guard->check(), 'User is authorized');
        $guard->setRequest($requestNew);
        $this->assertFalse($guard->check(), 'User is not authorized');
    }

    public function testPrimitiveValidation()
    {
        $falseTokenStr = $this->token_id . str_random(5);
        list($usersProvider, $user, $storage) = $this->getAuthObjects();
        $request = new Request(['api_token' => $this->token_id]);
        $guard = $this->createGuard($request, $usersProvider);
        //password will be skipped
        $userData = ['email' => $this->token_user_email, 'password' => $this->token_user_password];
        $userDataFake = ['email' => 'fake@example.com', 'password' => $this->token_user_password];
        $this->assertTrue($guard->validate($userData), 'User credentials are valid');
        $this->assertFalse($guard->validate($userDataFake), 'User credentials are not valid');
    }



    protected function getAuthObjects()
    {
        $storage = $this->createStorageMock();
        $storage->expects($this->any())
            ->method('getToken')
            ->withAnyParameters()
            ->willReturnCallback(function ($tokenId) {
                return $tokenId === $this->token_id ? $this->createToken() : null;
            });
        $this->app->bind('auth.tokencached.storage', function () use ($storage) { return $storage; });
        $usersProvider = $this->createUserProvider();
        $user = $this->createUser();
        $usersProvider->addUserToArray($user->toArray(), $user->getAuthIdentifier());

        return [$usersProvider, $user, $storage];
    }
}
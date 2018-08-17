<?php

namespace DigitSoft\LaravelTokenAuth\Guards;

use DigitSoft\LaravelTokenAuth\Contracts\AccessToken;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use Illuminate\Auth\GuardHelpers;
use DigitSoft\LaravelTokenAuth\Contracts\TokenGuard as Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

/**
 * Class TokenGuard
 * @package DigitSoft\LaravelTokenAuth\Guards
 */
class TokenGuard implements Guard
{
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var string
     */
    protected $inputKey;
    /**
     * @var AccessToken
     */
    protected $token;

    protected $no_reset = false;

    use GuardHelpers;

    /**
     * TokenGuard constructor.
     * @param UserProvider $userProvider
     * @param Request      $request
     * @param string       $inputKey
     */
    public function __construct(UserProvider $userProvider, Request $request, $inputKey = 'api_token')
    {
        $this->provider = $userProvider;
        $this->request = $request;
        $this->inputKey = $inputKey;
    }

    /**
     * Setter for request
     *
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        if (!$this->no_reset && $this->request !== $request) {
            $this->reset();
        }
        $this->request = $request;
    }

    /**
     * Get the token for the current request.
     *
     * @return string
     */
    public function getTokenForRequest()
    {
        $token = $this->request->query($this->inputKey);

        if (empty($token)) {
            $token = $this->request->input($this->inputKey);
        }

        if (empty($token)) {
            $token = $this->request->bearerToken();
        }

        if (empty($token)) {
            $token = $this->request->getPassword();
        }

        return $token;
    }

    /**
     * @return Storage
     */
    public function getStorage()
    {
        return app()->make(Storage::class);
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        $user = null;
        $userId = null;

        $token = $this->token();
        $userId = $token !== null ? $token->user_id : null;

        if ($userId !== null) {
            $user = $this->provider->retrieveById($userId);
        }

        return $this->user = $user;
    }

    /**
     * Get access token object for current request
     * @return AccessToken|null
     */
    public function token()
    {
        if ($this->token === null && ($tokenRequest = $this->getTokenForRequest()) !== null) {
            $this->token = $this->getStorage()->getToken($tokenRequest);
        }
        return $this->token;
    }

    /**
     * Set token object
     * @param AccessToken $token
     */
    public function setToken(AccessToken $token)
    {
        $this->token = $token;
    }

    /**
     * Validate a user's credentials.
     *
     * @param array $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        if ($this->provider->retrieveByCredentials($credentials)) {
            return true;
        }

        return false;
    }

    /**
     * Reset guard state
     */
    protected function reset()
    {
        $this->token = null;
        $this->user = null;
    }

    /**
     * Make guard not `resetable` for tests
     */
    public function fake()
    {
        $this->no_reset = true;
    }
}
<?php

namespace DigitSoft\LaravelTokenAuth\Guards;

use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

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
        if ($this->request !== $request) {
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
        return app('auth.tokencached.storage');
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

        $tokenRequest = $this->getTokenForRequest();

        if (!empty($tokenRequest)) {
            $token = $this->getStorage()->getToken($tokenRequest);
            $userId = $token !== null ? $token->user_id : null;
        }
        if ($userId !== null) {
            $user = $this->provider->retrieveById($userId);
        }

        return $this->user = $user;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
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
        $this->user = null;
    }

    /**
     * Get value from token data
     * @param array  $data
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    protected function getTokenData(array $data, $key, $default = null)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
}
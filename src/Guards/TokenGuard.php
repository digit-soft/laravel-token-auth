<?php

namespace DigitSoft\LaravelTokenAuth\Guards;

use Illuminate\Http\Request;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Eloquent\HasTokens;
use DigitSoft\LaravelTokenAuth\Facades\TokenCached;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken;
use DigitSoft\LaravelTokenAuth\Contracts\TokenGuard as Guard;
use DigitSoft\LaravelTokenAuth\Contracts\AlteredByAccessToken;

/**
 * Class TokenGuard
 */
class TokenGuard implements Guard
{
    use GuardHelpers;

    /**
     * @var \Illuminate\Http\Request
     */
    protected Request $request;
    /**
     * @var string
     */
    protected string $inputKey;
    /**
     * @var \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|null
     */
    protected ?AccessToken $token = null;
    /**
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected ?Authenticatable $lastAttempted = null;
    /**
     * Flag, do not reset user instance on new requests. (For tests)
     * @var bool
     */
    protected bool $no_reset = false;

    /**
     * TokenGuard constructor.
     *
     * @param  UserProvider $userProvider
     * @param  Request      $request
     * @param  string       $inputKey
     */
    public function __construct(UserProvider $userProvider, Request $request, string $inputKey = 'api_token')
    {
        $this->setProvider($userProvider);
        $this->request = $request;
        $this->inputKey = $inputKey;
    }

    /**
     * Setter for request
     *
     * @param  Request $request
     */
    public function setRequest(Request $request): void
    {
        if (! $this->no_reset && $this->request !== $request) {
            $this->reset();
        }
        $this->request = $request;
    }

    /**
     * Get the token for the current request.
     *
     * @return string|null
     */
    public function getTokenForRequest(): ?string
    {
        $token = $this->request->query($this->inputKey);
        $token = empty($token) ? $this->request->input($this->inputKey) : $token;
        $token = empty($token) ? $this->request->bearerToken() : $token;
        $token = empty($token) ? $this->request->getPassword() : $token;

        return is_string($token) ? $token : null;
    }

    /**
     * Get storage instance.
     *
     * @return \DigitSoft\LaravelTokenAuth\Contracts\Storage
     * @throws null
     */
    public function getStorage(): Storage
    {
        return app()->make(Storage::class);
    }

    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param array $credentials
     * @return bool
     */
    public function once(array $credentials = []): bool
    {
        /** @noinspection NotOptimalIfConditionsInspection */
        if ($this->validate($credentials) && ($user = $this->lastAttempted) !== null) {
            /** @var \Illuminate\Contracts\Auth\Authenticatable|HasTokens $user */
            $this->setUser($user);
            $this->setToken($user->createToken());

            return true;
        }

        return false;
    }

    /**
     * Log a user into the application without sessions or cookies externally checked and got.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|HasTokens $user
     * @return bool
     */
    public function onceExternal($user): bool
    {
        $this->lastAttempted = $user;
        $this->setUser($user);
        $this->setToken($user->createToken());

        return $this->token !== null;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|HasTokens|null
     */
    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->token();
        $userId = $token?->user_id;
        $user = $userId !== null ? $this->provider->retrieveById($userId) : null;

        // Set access token object
        if ($token !== null && $user instanceof AlteredByAccessToken) {
            $user->setAccessToken($token);
        }

        return $this->user = $user;
    }

    /**
     * Get access token object for current request.
     *
     * @return AccessToken|null
     */
    public function token(): ?AccessToken
    {
        if ($this->token === null && ($tokenRequest = $this->getTokenForRequest()) !== null && TokenCached::validateTokenStr($tokenRequest)) {
            $this->token = $this->getStorage()->getToken($tokenRequest);
        }

        return $this->token;
    }

    /**
     * Set token object.
     *
     * @param  AccessToken $token
     */
    public function setToken(AccessToken $token): void
    {
        $this->token = $token;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        return $this->hasValidCredentials($user, $credentials);
    }

    /**
     * Determine if the user matches the credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null $user
     * @param  array $credentials
     * @return bool
     */
    protected function hasValidCredentials(?Authenticatable $user, array $credentials): bool
    {
        return $user !== null && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Reset guard state.
     */
    public function reset(): void
    {
        $this->token = null;
        $this->user = null;
        $this->lastAttempted = null;
    }

    /**
     * MAKE GUARD NOT `RESETable` FOR TESTS
     */
    public function fake(): void
    {
        $this->no_reset = true;
    }
}

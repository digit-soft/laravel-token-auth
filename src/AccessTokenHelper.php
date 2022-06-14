<?php

namespace DigitSoft\LaravelTokenAuth;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Authenticatable;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use Illuminate\Config\Repository as ConfigRepository;
use DigitSoft\LaravelTokenAuth\Events\AccessTokenCreated;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;

class AccessTokenHelper
{
    protected int $ttl;
    protected int $ttlGuest;
    protected array $clientIds;
    protected string $clientIdDefault;

    public function __construct(ConfigRepository $config)
    {
        $this->configure($config);
    }

    /**
     * Get last added user token.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable         $user
     * @param  string|null                                        $client_id
     * @param  \DigitSoft\LaravelTokenAuth\Contracts\Storage|null $storage
     * @return \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|null
     * @throws null
     */
    public function getFirstFor(Authenticatable $user, ?string $client_id = null, ?Storage $storage = null): ?AccessTokenContract
    {
        $client_id = $client_id ?? $this->getDefaultClientId();
        $storage = $storage ?? app()->make(Storage::class);
        $userId = $user->getAuthIdentifier();
        $list = $storage->getUserTokens($userId, true);
        if (empty($list)) {
            return null;
        }
        foreach ($list as $token) {
            if ($token->client_id === $client_id) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Create new token for user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string|null                                     $client_id
     * @param  bool                                       $autoTtl
     * @return \DigitSoft\LaravelTokenAuth\Contracts\AccessToken
     */
    public function createFor(Authenticatable $user, ?string $client_id = null, bool $autoTtl = true): AccessTokenContract
    {
        if ($client_id === null) {
            $client_id = $this->getDefaultClientId();
        }
        $data = [
            'user_id' => $user->getAuthIdentifier(),
            'client_id' => $client_id,
        ];
        $token = $this->createFromData($data);
        $token->ensureUniqueness();
        if ($autoTtl) {
            $token->setTtl(config('auth-token.ttl'));
        }
        AccessTokenCreated::dispatch($token);

        return $token;
    }

    /**
     * Create new token for guest.
     *
     * @param  string|null $client_id
     * @param  bool        $autoTtl
     * @return \DigitSoft\LaravelTokenAuth\Contracts\AccessToken
     */
    public function createForGuest(?string $client_id = null, bool $autoTtl = true): AccessTokenContract
    {
        if ($client_id === null) {
            $client_id = $this->getDefaultClientId();
        }
        $data = [
            'user_id' => AccessTokenContract::USER_ID_GUEST,
            'client_id' => $client_id,
        ];
        $token = $this->createFromData($data);
        $token->ensureUniqueness();
        if ($autoTtl) {
            $token->setTtl(config('auth-token.ttl_guest'));
        }
        AccessTokenCreated::dispatch($token);

        return $token;
    }

    /**
     * Create token instance from data array.
     *
     * @param  array $data
     * @param  bool  $fromStorage
     * @return \DigitSoft\LaravelTokenAuth\Contracts\AccessToken
     * @throws null
     */
    public function createFromData(array $data = [], bool $fromStorage = false): AccessTokenContract
    {
        return app()->make(AccessTokenContract::class, ['config' => $data, 'fromStorage' => $fromStorage]);
    }

    /**
     * Remove all tokens for a user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @throws null
     */
    public function removeAllFor(Authenticatable $user): void
    {
        if (($userId = $user->getAuthIdentifier()) === null) {
            return;
        }
        /** @var Storage $storage */
        $storage = app()->make(Storage::class);
        $list = $storage->getUserTokens($userId, true);
        if (empty($list)) {
            return;
        }
        foreach ($list as $token) {
            $storage->removeToken($token);
        }
    }

    /**
     * Get default client ID
     *
     * @return string
     */
    public function getDefaultClientId(): string
    {
        return $this->clientIdDefault;
    }

    /**
     * Get client ID from request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return string
     */
    public function getClientIdFromRequest(Request $request): string
    {
        if (($clientId = $request->input(AccessTokenContract::REQUEST_CLIENT_ID_PARAM)) !== null && $this->validateClientId($clientId)) {
            return $clientId;
        }
        if (($clientId = $request->header(AccessTokenContract::REQUEST_CLIENT_ID_HEADER)) !== null && $this->validateClientId($clientId)) {
            return $clientId;
        }

        return $this->getDefaultClientId();
    }

    /**
     * Generate random token string.
     *
     * @return string
     * @throws null
     */
    public function generateTokenStr(): string
    {
        $randLength = config('auth-token.token_length', 60);
        $randomStr = Str::random($randLength);
        $hash = hash('sha256', $randomStr);
        $hashLn = 64; //for sha256 (256/4)
        for ($i = 0; $i < $hashLn; $i++) {
            if (! is_numeric($hash[$i]) && random_int(0, 1) % 2) {
                $hash[$i] = strtoupper($hash[$i]);
            }
        }
        $pos = ceil($randLength / 2);

        return substr($randomStr, 0, $pos) . $hash . substr($randomStr, $pos);
    }

    /**
     * Validate token string.
     *
     * @param  string $token
     * @return bool
     */
    public function validateTokenStr(string $token): bool
    {
        $randLength = config('auth-token.token_length', 60);
        $hashLn = 64; //for sha256 (256/4)
        if (strlen($token) !== ($randLength + $hashLn)) {
            return false;
        }
        $pos = ceil($randLength / 2);
        $randStr = substr($token, 0, $pos) . substr($token, -($randLength - $pos));
        $hash = strtolower(substr($token, $pos, $hashLn));

        return hash('sha256', $randStr) === $hash;
    }

    /**
     * Check that client ID is valid.
     *
     * @param  string $client_id
     * @return bool
     */
    protected function validateClientId(mixed $client_id): bool
    {
        return is_string($client_id) && in_array($client_id, $this->clientIds, true);
    }

    /**
     * Configure self.
     *
     * @param  \Illuminate\Config\Repository $config
     * @return void
     */
    protected function configure(ConfigRepository $config): void
    {
        $this->ttl = $config->get('auth-token.ttl');
        $this->ttlGuest = $config->get('auth-token.ttl_guest');
        $this->clientIdDefault = $config->get('auth-token.client_id_default');
        $this->clientIds = $config->get('auth-token.client_ids', [$this->clientIdDefault]);
    }
}

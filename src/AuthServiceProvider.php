<?php

namespace DigitSoft\LaravelTokenAuth;

use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Contracts\TokenGuard as TokenGuardContract;
use DigitSoft\LaravelTokenAuth\Guards\TokenGuard;
use DigitSoft\LaravelTokenAuth\Session\TokenSessionHandler;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Session\SessionManager;
use Illuminate\Support\ServiceProvider;

/**
 * Class AuthServiceProvider
 * @package DigitSoft\LaravelTokenAuth
 * @codeCoverageIgnore
 */
class AuthServiceProvider extends ServiceProvider
{
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__ . '/../config/auth-token.php';
        if (function_exists('config_path')) {
            $publishPath = config_path('auth-token.php');
        } else {
            $publishPath = base_path('config/auth-token.php');
        }
        $this->publishes([$configPath => $publishPath], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/auth-token.php';
        $this->mergeConfigFrom($configPath, 'auth-token');

        $this->registerHelper();
        $this->registerTokenClass();
        $this->registerStorage();
        $this->registerTokenGuard();
        $this->registerSessionHandler();
    }

    /**
     * Register helper class instance
     */
    protected function registerHelper()
    {
        $this->app->singleton('auth-token', function ($app) {
            return new AccessTokenHelper($app['config']);
        });

        $this->app->alias('auth-token', AccessTokenHelper::class);
    }

    /**
     * Bind token object builder
     */
    protected function registerTokenClass()
    {
        $this->app->bind('auth-token.token', function ($app, $params = []) {
            /** @var Application $app */
            $tokenClass = $app['config']['auth-token.token_class'];
            $token = $app->make($tokenClass, $params);
            return $token;
        });

        $this->app->alias('auth-token.token', AccessTokenContract::class);
    }

    /**
     * Register token store instance
     */
    protected function registerStorage()
    {
        $this->app->singleton('auth-token.storage', function ($app) {
            /** @var Application $app */
            $storageClass = $app['config']['auth-token.storage_class'];
            $storage = new $storageClass($app['config']);

            return $storage;
        });

        $this->app->alias('auth-token.storage', Storage::class);
    }

    /**
     * Register token guard
     */
    protected function registerTokenGuard()
    {
        $this->app->singleton('auth-token.guard', function ($app, $params = []) {
            /** @var Application $app */
            /** @var AuthManager $auth */
            if (!isset($params['userProvider'])) {
                $auth = $app['auth'];
                $providerName = config('auth.guards.api.provider', null);
                $params['userProvider'] = $auth->createUserProvider($providerName);
            }
            if (!isset($params['request'])) {
                $params['request'] = $app['request'];
            }
            $guard = $app->make(TokenGuard::class, $params);

            $app->refresh('request', $guard, 'setRequest');

            return $guard;
        });

        $this->app->alias('auth-token.guard', TokenGuardContract::class);

        $this->app->alias('auth-token.storage', Storage::class);
        $this->app->resolving('auth', function ($auth) {
            /** @var AuthManager $auth */
            $auth->extend('token-cached', function ($app, $name, $config) {
                /** @var Application $app */
                /** @var AuthManager $auth */
                $auth = $app['auth'];
                $userProvider = $auth->createUserProvider($config['provider'] ?? null);
                return $app->make('auth-token.guard', [
                    'userProvider' => $userProvider,
                    'request' => $app['request'],
                ]);
            });
        });
    }

    /**
     * Register token session handler
     */
    public function registerSessionHandler()
    {
        $this->app->resolving('session', function ($manager) {
            /** @var SessionManager $manager */
            $manager->extend('token-cached', function ($app) {
                /** @var Application $app */
                return $app->make(TokenSessionHandler::class);
            });
        });
    }
}

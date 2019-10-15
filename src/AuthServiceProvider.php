<?php

namespace DigitSoft\LaravelTokenAuth;

use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Session\SessionManager;
use Illuminate\Support\ServiceProvider;
use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Guards\TokenGuard;
use DigitSoft\LaravelTokenAuth\Session\TokenSessionHandler;
use DigitSoft\LaravelTokenAuth\Traits\WithAuthTokenStorage;
use DigitSoft\LaravelTokenAuth\Contracts\TokenGuard as TokenGuardContract;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken as AccessTokenContract;

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

            return $app->make($tokenClass, $params);
        });

        $this->app->alias('auth-token.token', AccessTokenContract::class);
    }

    /**
     * Register token store instance
     */
    protected function registerStorage()
    {
        // Register rebinding callback for storage
        $this->app->rebinding('auth-token.storage', function ($app, $instance) {
            WithAuthTokenStorage::$_authTokenStorageInstance = $instance;
        });

        $this->app->singleton('auth-token.storage', function ($app) {
            /** @var Application $app */
            $storageClass = $app['config']['auth-token.storage_class'];
            $storage = new $storageClass($app['config'], $app['redis']);

            $app->refresh('redis', $storage, 'setManager');

            return $storage;
        });

        $this->app->alias('auth-token.storage', Storage::class);
    }

    /**
     * Register token guard
     */
    protected function registerTokenGuard()
    {
        $this->app->bind('auth-token.guard', function ($app, $params = []) {
            /** @var Application $app */
            /** @var AuthManager $auth */
            $guard = $auth->guard('api');
            if (! $guard instanceof TokenGuardContract) {
                throw new \Exception('Guard "api" has invalid configuration');
            }
            return $guard;
        });

        $this->app->alias('auth-token.guard', TokenGuardContract::class);

        $this->app->resolving('auth', function ($auth) {
            /** @var AuthManager $auth */
            // Register provider
            $auth->provider('eloquent-token', function ($app, $config) {
                return new EloquentUserProvider($app['hash'], $config['model'], $config['model_guest']);
            });

            // Register driver
            $auth->extend('token-cached', function ($app, $name, $config) {
                /** @var Application $app */
                /** @var AuthManager $auth */
                $auth = $app['auth'];
                $userProvider = $auth->createUserProvider($config['provider'] ?? null);

                $guard = $app->make(TokenGuard::class, [
                    'userProvider' => $userProvider,
                    'request' => $app['request'],
                ]);

                $app->refresh('request', $guard, 'setRequest');

                return $guard;
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

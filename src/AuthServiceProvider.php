<?php

namespace DigitSoft\LaravelTokenAuth;

use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Guards\TokenGuard;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class AuthServiceProvider
 * @package DigitSoft\LaravelTokenAuth
 */
class AuthServiceProvider extends ServiceProvider
{
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

        $this->registerTokenClass();
        $this->registerStorage();
        $this->registerTokenGuard();
    }

    /**
     * Bind token object builder
     */
    protected function registerTokenClass()
    {
        $this->app->bind('auth.tokencached.token', function ($app, $params = []) {
            /** @var Application $app */
            $tokenClass = $app['config']['auth-token.token_class'];
            $token = $app->make($tokenClass, $params);
            return $token;
        });

        $this->app->alias('auth.tokencached.token', \DigitSoft\LaravelTokenAuth\Contracts\AccessToken::class);
    }

    /**
     * Register token store instance
     */
    protected function registerStorage()
    {
        $this->app->singleton('auth.tokencached.storage', function ($app) {
            /** @var Application $app */
            $storageClass = $app['config']['auth-token.storage_class'];
            $storage = new $storageClass($app['config']);

            return $storage;
        });

        $this->app->alias('auth.tokencached.storage', Storage::class);
    }

    /**
     * Register token guard
     */
    protected function registerTokenGuard()
    {
        $this->app->resolving('auth', function ($auth){
            /** @var AuthManager $auth */
            $auth->extend('token-cached', function ($app, $name, $config) {
                /** @var Application $app */
                /** @var AuthManager $auth */
                $auth = $app['auth'];
                $guard = new TokenGuard(
                    $auth->createUserProvider($config['provider'] ?? null),
                    $app['request']
                );

                $app->refresh('request', $guard, 'setRequest');

                return $guard;
            });
        });
    }
}

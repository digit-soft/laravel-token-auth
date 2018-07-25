<?php
namespace DigitSoft\LaravelTokenAuth;

use DigitSoft\LaravelTokenAuth\Contracts\Storage;
use DigitSoft\LaravelTokenAuth\Guards\TokenGuard;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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

        $this->registerStorage();
        $this->registerTokenGuard();
    }

    /**
     * Register token store instance
     */
    protected function registerStorage()
    {
        $this->app->singleton('auth.tokencached.storage', function ($app) {
            /** @var Application $app */
            $storageName = $app['config']['auth-token.storage'];
            $storageClass = 'DigitSoft\\LaravelTokenAuth\\Storage\\' . Str::ucfirst($storageName);
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

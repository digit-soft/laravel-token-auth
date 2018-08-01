# Token auth for Laravel 5.6+

_Component uses Redis to store token information_

### Install
```
composer require "digit-soft/laravel-token-auth:~1.0"
```

### Publish config
```
php artisan vendor:publish --provider="DigitSoft\LaravelTokenAuth\AuthServiceProvider" --tag=config
```

### Configure guard
Change driver to `token-cached` in **_config/auth.php_**
```php
    'guards' => [
        ...
        'api' => [
            'driver' => 'token-cached',
            'provider' => 'users',
        ],
        ...
    ],
```

### Goes with multiple middleware
* `DigitSoft\LaravelTokenAuth\Middleware\DefaultAuthGuard` - set default auth guard to given value
* `DigitSoft\LaravelTokenAuth\Middleware\DefaultSessionDriver` - set default session driver to given value
* `DigitSoft\LaravelTokenAuth\Middleware\AddGeneratedTokenToResponse` - adds token generated during request to response header
Some middleware useful for PHP-PM driven applications.

### Sessions
There is possibility to save session data to token, if you will use package session handler `DigitSoft\LaravelTokenAuth\Session\TokenSessionHandler`, driver name is `token-cached`. You can also set it with `DefaultSessionDriver` middleware.
Often rewritable data such as **previous page**, **flashes** and **_token** are not stored.

### Events
There is `DigitSoft\LaravelTokenAuth\Events\AccessTokenCreated`, which fired every time token is created by `TokenCached` facade.
So you can write or check information in `AccessToken` with own event listener.

### Customization
* You can use your own `AccessToken` implementation by extending original class and set its name in config.
* You can set your own header name for `AddGeneratedTokenToResponse` middleware in config.

_You can find more options and description in config file._
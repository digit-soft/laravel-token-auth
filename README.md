# Token auth for Laravel 5.6+

_Comonent uses Redis to store token information_

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
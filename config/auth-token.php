<?php

return array(
    /*
    |--------------------------------------------------------------------------
    | Storage class name
    |--------------------------------------------------------------------------
    |
    | Class, as storage implementation
    */
    'storage_class' => 'DigitSoft\LaravelTokenAuth\Storage\Redis',
    /*
    |--------------------------------------------------------------------------
    | Token class name
    |--------------------------------------------------------------------------
    |
    | Class, that will be used for AccessToken
    */
    'token_class' => 'DigitSoft\LaravelTokenAuth\AccessToken',
    /*
    |--------------------------------------------------------------------------
    | Token length
    |--------------------------------------------------------------------------
    |
    | Token string length
    */
    'token_length' => 60,
    /*
    |--------------------------------------------------------------------------
    | Token TTL
    |--------------------------------------------------------------------------
    |
    | Time To Live for token in seconds (default year)
    */
    'ttl' => 31536000,
    /*
    |--------------------------------------------------------------------------
    | Guest Token TTL
    |--------------------------------------------------------------------------
    |
    | Time To Live for guest token in seconds (default 24 hours)
    */
    'ttl_guest' => 86400,
    /*
    |--------------------------------------------------------------------------
    | Connection name
    |--------------------------------------------------------------------------
    |
    | What connection to use (for redis)
    */
    'connection' => null,
    /*
    |--------------------------------------------------------------------------
    | Token entries prefix
    |--------------------------------------------------------------------------
    |
    | Prefix used to generate keys token entries in storage
    */
    'token_prefix' => 'tkn:',
    /*
    |--------------------------------------------------------------------------
    | User entries prefix
    |--------------------------------------------------------------------------
    |
    | Prefix used to generate keys for user => token assign entries in storage
    */
    'user_prefix' => 'tkn:usr:',
    /*
    |--------------------------------------------------------------------------
    | Valid client identifiers
    |--------------------------------------------------------------------------
    |
    | List of valid client IDs, used to validate client ID from request
    */
    'client_ids' => ['api'],
    /*
    |--------------------------------------------------------------------------
    | Default client ID
    |--------------------------------------------------------------------------
    */
    'client_id_default' => 'api',
    /*
    |--------------------------------------------------------------------------
    | Automatically create guest token with session start
    |--------------------------------------------------------------------------
    | Can be useful together with middleware `AddGeneratedTokenToResponse`
    | which will return created token to user in headers
    */
    'session_token_autocreate' => true,
    /*
    |--------------------------------------------------------------------------
    | Response header name with token
    |--------------------------------------------------------------------------
    | Response header name that will contain user auth token
    */
    'response_token_header' => 'Auth-token',
);

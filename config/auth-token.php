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
    'user_prefix' => 'usr:tkns:',
    /*
    |--------------------------------------------------------------------------
    | Valid client identifiers
    |--------------------------------------------------------------------------
    |
    | List of valid client IDs, used to validate client ID from request
    */
    'client_ids' => ['api'],
);
<?php

return array(
    'storage_class' => 'DigitSoft\LaravelTokenAuth\Storage\Redis',
    'token_class' => 'DigitSoft\LaravelTokenAuth\AccessToken',
    'token_length' => 60,
    'ttl' => 5,
    'connection' => null,
    'token_prefix' => 'tkn:',
    'user_prefix' => 'usr:tkns:',
);
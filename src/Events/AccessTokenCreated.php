<?php

namespace DigitSoft\LaravelTokenAuth\Events;

use DigitSoft\LaravelTokenAuth\AccessToken;
use Illuminate\Queue\SerializesModels;

/**
 * Event AccessTokenCreated.
 * You can use this event to fill token fields with own data
 * @package DigitSoft\LaravelTokenAuth\Events
 */
class AccessTokenCreated
{
    use SerializesModels;

    /**
     * @var AccessToken
     */
    public $token;

    /**
     * AccessTokenCreated constructor.
     * @param AccessToken $token
     */
    public function __construct(AccessToken $token)
    {
        $this->token = $token;
    }
}
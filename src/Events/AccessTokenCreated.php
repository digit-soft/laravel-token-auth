<?php

namespace DigitSoft\LaravelTokenAuth\Events;

use App\Events\Event;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken;
use Illuminate\Queue\SerializesModels;

/**
 * Event AccessTokenCreated.
 * You can use this event to fill token fields with own data
 *
 * @package DigitSoft\LaravelTokenAuth\Events
 */
class AccessTokenCreated extends Event
{
    use SerializesModels;

    /**
     * @var \DigitSoft\LaravelTokenAuth\Contracts\AccessToken
     */
    public $token;

    /**
     * AccessTokenCreated constructor.
     * @param \DigitSoft\LaravelTokenAuth\Contracts\AccessToken $token
     */
    public function __construct(AccessToken $token)
    {
        $this->token = $token;
    }
}

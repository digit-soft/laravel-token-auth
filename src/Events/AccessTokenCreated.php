<?php

namespace DigitSoft\LaravelTokenAuth\Events;

use App\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use DigitSoft\LaravelTokenAuth\Contracts\AccessToken;

/**
 * Event AccessTokenCreated.
 * You can use this event to fill token fields with own data
 *
 * @method static void dispatch(AccessToken $token)
 */
class AccessTokenCreated extends Event
{
    use Dispatchable, SerializesModels;

    /**
     * @var \DigitSoft\LaravelTokenAuth\Contracts\AccessToken
     */
    public $token;

    /**
     * AccessTokenCreated constructor.
     *
     * @param \DigitSoft\LaravelTokenAuth\Contracts\AccessToken $token
     */
    public function __construct(AccessToken $token)
    {
        $this->token = $token;
    }
}

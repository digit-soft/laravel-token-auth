<?php

namespace DigitSoft\LaravelTokenAuth\Middleware;

use Illuminate\Http\Request;
use Illuminate\Contracts\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use DigitSoft\LaravelTokenAuth\Traits\WithAuthGuardHelpersForSession;
use Illuminate\Session\Middleware\StartSession as LaravelStartSession;

class StartSession extends LaravelStartSession
{
    use WithAuthGuardHelpersForSession;

    /**
     * {@inheritdoc}
     */
    protected function addCookieToResponse(Response $response, Session $session)
    {
        // Do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function getSession(Request $request)
    {
        return $this->manager->driver();
    }
}

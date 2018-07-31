<?php

namespace DigitSoft\LaravelTokenAuth\Middleware;

use Illuminate\Auth\AuthManager;

/**
 * Class DefaultAuthGuard.
 * Middleware that sets default auth guard driver on each request.
 * Feature is useful for applications, those work on top of React PHP (PHP PM)
 * @package DigitSoft\LaravelTokenAuth\Middleware
 */
class DefaultAuthGuard
{
    /**
     * @var AuthManager
     */
    protected $manager;

    /**
     * DefaultAuthGuard constructor.
     * @param AuthManager $manager
     */
    public function __construct(AuthManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, \Closure $next, $guard = 'web')
    {
        if ($guard !== $this->manager->getDefaultDriver()) {
            $this->manager->shouldUse($guard);
        }

        return $next($request);
    }
}
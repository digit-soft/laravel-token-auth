<?php

namespace DigitSoft\LaravelTokenAuth\Middleware;

use Illuminate\Auth\AuthManager;

/**
 * Class DefaultAuthGuard.
 *
 * Middleware that sets default auth guard driver on each request.
 * Feature is useful for applications, those work on top of React PHP (PHP PM)
 *
 * @codeCoverageIgnore
 */
class DefaultAuthGuard
{
    /**
     * @var \Illuminate\Auth\AuthManager
     */
    protected $manager;

    /**
     * DefaultAuthGuard constructor.
     *
     * @param  \Illuminate\Auth\AuthManager $manager
     */
    public function __construct(AuthManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @param  string|null              $guard
     * @return mixed
     */
    public function handle($request, \Closure $next, ?string $guard = 'web')
    {
        if ($guard !== $this->manager->getDefaultDriver()) {
            $this->manager->shouldUse($guard);
        }

        return $next($request);
    }
}

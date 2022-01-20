<?php

namespace DigitSoft\LaravelTokenAuth\Middleware;

use Illuminate\Session\SessionManager;

/**
 * Class DefaultSessionDriver.
 *
 * Middleware that sets default auth session driver on each request.
 * Feature is useful for applications, those work on top of React PHP (PHP PM)
 *
 * @codeCoverageIgnore
 */
class DefaultSessionDriver
{
    /**
     * @var \Illuminate\Session\SessionManager
     */
    protected $manager;

    /**
     * SetDefaultSessionDriver middleware constructor.
     *
     * @param \Illuminate\Session\SessionManager $manager
     */
    public function __construct(SessionManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @param  string                   $driver
     * @return mixed
     */
    public function handle($request, \Closure $next, string $driver = 'array')
    {
        if ($driver !== $this->manager->getDefaultDriver()) {
            $this->manager->setDefaultDriver($driver);
        }

        return $next($request);
    }
}

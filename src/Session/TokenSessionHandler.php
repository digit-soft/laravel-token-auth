<?php

namespace DigitSoft\LaravelTokenAuth\Session;

use Illuminate\Support\Arr;
use SessionHandlerInterface;
use Illuminate\Config\Repository;
use DigitSoft\LaravelTokenAuth\Traits\WithAuthGuardHelpersForSession;

/**
 * Class TokenSessionHandler
 */
class TokenSessionHandler implements SessionHandlerInterface
{
    use WithAuthGuardHelpersForSession;

    protected bool $createGuestTokenAutomatically = false;

    /**
     * TokenSessionHandler constructor.
     * @param \Illuminate\Config\Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->createGuestTokenAutomatically = $config->get('auth-token.session_token_autocreate', false);
    }

    /**
     * Close the session
     * @link http://php.net/manual/en/sessionhandlerinterface.close.php
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Destroy a session
     * @link http://php.net/manual/en/sessionhandlerinterface.destroy.php
     * @param string $id The session ID being destroyed.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function destroy($id): bool
    {
        return $this->saveSessionData(null);
    }

    /**
     * Cleanup old sessions
     * @link http://php.net/manual/en/sessionhandlerinterface.gc.php
     * @param int $max_lifetime <p>
     * Sessions that have not updated for
     * the last maxlifetime seconds will be removed.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function gc($max_lifetime): int|false
    {
        return 1;
    }

    /**
     * Initialize session
     * @link http://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $path The path where to store/retrieve the session.
     * @param string $name The session name.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function open($path, $name): bool
    {
        return true;
    }

    /**
     * Read session data
     * @link http://php.net/manual/en/sessionhandlerinterface.read.php
     * @param string $id The session id to read data for.
     * @return string <p>
     * Returns an encoded string of the read data.
     * If nothing was read, it must return an empty string.
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function read($id): string|false
    {
        $token = $this->getToken();
        if ($token !== null) {
            return $token->session;
        }

        return false;
    }

    /**
     * Write session data
     * @link http://php.net/manual/en/sessionhandlerinterface.write.php
     * @param string $id The session id.
     * @param string $data <p>
     * The encoded session data. This data is the
     * result of the PHP internally encoding
     * the $_SESSION superglobal to a serialized
     * string and passing it as this parameter.
     * Please note sessions use an alternative serialization method.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function write($id, $data): bool
    {
        return $this->saveSessionData(
            $this->cleanupSessionData($data)
        );
    }

    /**
     * Save session data
     *
     * @param  string|null $data
     * @return bool
     */
    protected function saveSessionData($data): bool
    {
        $token = $this->getToken();
        if ($token !== null && $token->session !== $data) {
            $token->session = $data !== '' ? $data : null;
            $token->save();
        }

        return true;
    }

    /**
     * Remove some data from session array.
     * We do not need to save such session data as '_previous', '_token', '_flash',
     * only variables that were obviously set by user to prevent token rewrite each time.
     *
     * @param  string $data
     * @return string
     */
    protected function cleanupSessionData($data)
    {
        $remove = ['_previous', '_token', '_flash', 'PHPDEBUGBAR_STACK_DATA'];
        if ($data === null || $data === '') {
            return $data;
        }
        // We will use serialize/unserialize because of laravel use it internally to handle session data
        // instead of standard PHP session_encode()/session_decode()
        $session = @unserialize($data);
        if ($session === null) {
            return serialize([]);
        }
        foreach ($remove as $key) {
            Arr::forget($session, $key);
        }

        return serialize($session);
    }

    /**
     * {@inheritdoc}
     */
    protected function shouldCreateGuestTokenWhenMissing(): bool
    {
        return $this->createGuestTokenAutomatically;
    }
}

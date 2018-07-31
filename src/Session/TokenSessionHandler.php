<?php

namespace DigitSoft\LaravelTokenAuth\Session;

use DigitSoft\LaravelTokenAuth\Contracts\TokenGuard;
use Illuminate\Config\Repository;
use Illuminate\Support\Arr;
use SessionHandlerInterface;

/**
 * Class TokenSessionHandler
 * @package DigitSoft\LaravelTokenAuth\Session
 */
class TokenSessionHandler implements SessionHandlerInterface
{
    /**
     * App config repository
     * @var Repository
     */
    protected $config;

    /**
     * TokenSessionHandler constructor.
     * @param \Illuminate\Config\Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
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
    public function close()
    {
        return true;
    }

    /**
     * Destroy a session
     * @link http://php.net/manual/en/sessionhandlerinterface.destroy.php
     * @param string $session_id The session ID being destroyed.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function destroy($session_id)
    {
        return $this->saveSessionData(null);
    }

    /**
     * Cleanup old sessions
     * @link http://php.net/manual/en/sessionhandlerinterface.gc.php
     * @param int $maxlifetime <p>
     * Sessions that have not updated for
     * the last maxlifetime seconds will be removed.
     * </p>
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function gc($maxlifetime)
    {
        return true;
    }

    /**
     * Initialize session
     * @link http://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $save_path The path where to store/retrieve the session.
     * @param string $name The session name.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * Read session data
     * @link http://php.net/manual/en/sessionhandlerinterface.read.php
     * @param string $session_id The session id to read data for.
     * @return string <p>
     * Returns an encoded string of the read data.
     * If nothing was read, it must return an empty string.
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function read($session_id)
    {
        $token = $this->getToken();
        if ($token !== null) {
            return $token->session;
        }
        return '';
    }

    /**
     * Write session data
     * @link http://php.net/manual/en/sessionhandlerinterface.write.php
     * @param string $session_id The session id.
     * @param string $session_data <p>
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
    public function write($session_id, $session_data)
    {
        $session_data = $this->cleanupSessionData($session_data);
        return $this->saveSessionData($session_data);
    }

    /**
     * Save session data
     * @param string $session_data
     * @return bool
     */
    protected function saveSessionData($session_data)
    {
        $token = $this->getToken();
        if ($token !== null && $token->session !== $session_data) {
            $token->session = $session_data !== '' ? $session_data : null;
            $token->save();
        }
        return true;
    }

    /**
     * Remove some data from session array.
     * We do not need to save such session data as '_previous', '_token', '_flash',
     * only variables that were obviously set by user to prevent token rewrite each time.
     * @param string $session_data
     * @return string
     */
    protected function cleanupSessionData($session_data)
    {
        $remove = ['_previous', '_token', '_flash'];
        if ($session_data === null || $session_data === '') {
            return $session_data;
        }
        // We will use serialize/unserialize because of laravel use it internally to handle session data
        // instead of standard PHP session_encode()/session_decode()
        $session = unserialize($session_data);
        foreach ($remove as $key) {
            Arr::forget($session, $key);
        }
        $session_data = serialize($session);
        return $session_data;
    }

    /**
     * Get current token to save data
     * @return \DigitSoft\LaravelTokenAuth\Contracts\AccessToken|null
     */
    protected function getToken()
    {
        $guard = \Auth::guard();
        if (!$guard instanceof TokenGuard || ($token = $guard->token()) === null) {
            return null;
        }
        return $token;
    }
}
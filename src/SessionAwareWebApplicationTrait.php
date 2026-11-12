<?php

/**
 * Part of the Joomla Framework Application Package
 *
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application;

use Joomla\Input\Input;
use Joomla\Session\SessionInterface;

/**
 * Trait which helps implementing `Joomla\Application\SessionAwareWebApplicationInterface` in a web application class.
 *
 * @since  2.0.0
 */
trait SessionAwareWebApplicationTrait
{
    /**
     * The application session object.
     *
     * @var    SessionInterface
     * @since  2.0.0
     */
    protected $session;

    /**
     * Method to get the application input object.
     *
     * @return  Input
     *
     * @since   2.0.0
     */
    abstract public function getInput(): Input;

    /**
     * Method to get the application session object.
     *
     * @return  SessionInterface  The session object
     *
     * @since   2.0.0
     */
    public function getSession()
    {
        if ($this->session === null) {
            throw new \RuntimeException(\sprintf('A %s object has not been set.', SessionInterface::class));
        }

        return $this->session;
    }

    /**
     * Sets the session for the application to use, if required.
     *
     * @param   SessionInterface  $session  A session object.
     *
     * @return  $this
     *
     * @since   2.0.0
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Checks for a form token in the request.
     *
     * A token sent in the X-CSRF-Token header must match the session token. Otherwise the request must contain a
     * variable named after the session token.
     *
     * @param   string  $method  The request method in which to look for the token key, either "post" or "get".
     *
     * @return  boolean
     *
     * @throws  \InvalidArgumentException  If the request method is not supported.
     * @since   2.0.0
     */
    public function checkToken($method = 'post')
    {
        $method = \strtolower((string) $method);

        if (!\in_array($method, ['post', 'get'], true)) {
            throw new \InvalidArgumentException(
                \sprintf('The request method "%s" is not supported for the token check, use "post" or "get".', $method)
            );
        }

        $token = $this->getFormToken();

        // Support a token sent via the X-CSRF-Token header, then fall back to a variable named after the token
        $requestToken = $this->getInput()->server->get('HTTP_X_CSRF_TOKEN', '', 'raw');

        if (!\is_string($requestToken) || $requestToken === '') {
            if (!$this->getInput()->$method->get($token, '', 'alnum')) {
                return false;
            }

            $requestToken = $token;
        }

        $valid = \hash_equals($token, $requestToken);

        // Let the session see the result as well, it expires the session when the check fails
        return $this->getSession()->hasToken($valid ? $token : '') && $valid;
    }

    /**
     * Method to determine a hash for anti-spoofing variable names
     *
     * @param   boolean  $forceNew  If true, force a new token to be created
     *
     * @return  string  Hashed var name
     *
     * @since   2.0.0
     */
    public function getFormToken($forceNew = false)
    {
        return $this->getSession()->getToken($forceNew);
    }
}

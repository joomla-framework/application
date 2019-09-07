<?php
/**
 * Part of the Joomla Framework Application Package
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application;

use Joomla\Application\Exception\UnableToWriteBody;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\Session\SessionInterface;
use Joomla\Uri\Uri;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * Base class for a session aware Joomla! Web application.
 *
 * @since  __DEPLOY_VERSION__
 */
abstract class AbstractSessionAwareWebApplication extends AbstractWebApplication implements SessionAwareWebApplicationInterface
{
	/**
	 * The application session object.
	 *
	 * @var    SessionInterface
	 * @since  __DEPLOY_VERSION__
	 */
	protected $session;

	/**
	 * Method to get the application session object.
	 *
	 * @return  SessionInterface  The session object
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getSession()
	{
		if ($this->session === null)
		{
			throw new \RuntimeException(
				\sprintf(
					'A %s object has not been set.',
					SessionInterface::class
				)
			);
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
	 * @since   __DEPLOY_VERSION__
	 */
	public function setSession(SessionInterface $session)
	{
		$this->session = $session;

		return $this;
	}

	/**
	 * Checks for a form token in the request.
	 *
	 * @param   string  $method  The request method in which to look for the token key.
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function checkToken($method = 'post')
	{
		$token = $this->getFormToken();

		// Support a token sent via the X-CSRF-Token header, then fall back to a token in the request
		$requestToken = $this->input->server->get(
			'HTTP_X_CSRF_TOKEN',
			$this->input->$method->get($token, '', 'alnum'),
			'alnum'
		);

		if (!$requestToken)
		{
			return false;
		}

		return $this->session->hasToken($token);
	}

	/**
	 * Method to determine a hash for anti-spoofing variable names
	 *
	 * @param   boolean  $forceNew  If true, force a new token to be created
	 *
	 * @return  string  Hashed var name
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getFormToken($forceNew = false)
	{
		return $this->session->getToken($forceNew);
	}
}

<?php
/**
 * Part of the Joomla Framework Application Package
 *
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application;

use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Joomla\DI\Container;

/**
 * Joomla Framework Base Application Class
 *
 * @since  1.0
 */
abstract class AbstractApplication implements LoggerAwareInterface
{
	/**
	 * The application configuration object.
	 *
	 * @var    Registry
	 * @since  1.0
	 *
	 * @deprecated 2.0, use getConfig() instead
	 */
	protected $config;

	/**
	 * The application input object.
	 *
	 * @var    Input
	 * @since  1.0
	 *
	 * @deprecated 2.0, use getInput() instead
	 */
	public $input = null;

	/**
	 * The DI container.
	 *
	 * @var    Container
	 * @since  1.0
	 */
	private $container;

	/**
	 * Class constructor.
	 *
	 * @param   Input     $input   An optional argument to provide dependency injection for the application's
	 *                             input object.  If the argument is a InputCli object that object will become
	 *                             the application's input object, otherwise a default input object is created.
	 * @param   Registry  $config  An optional argument to provide dependency injection for the application's
	 *                             config object.  If the argument is a Registry object that object will become
	 *                             the application's config object, otherwise a default config object is created.
	 *
	 * @since   1.0
	 */
	public function __construct(Input $input = null, Registry $config = null)
	{
		$this->input = $input instanceof Input ? $input : new Input;
		$this->config = $config instanceof Registry ? $config : new Registry;

		// Create the container
		$container = $this->getContainer();

		// Set the input
		$container->set('Joomla\\Input\\Input', $this->input, false, true);
		$container->alias('Input', 'Joomla\\Input\\Input');
		$container->alias('input', 'Joomla\\Input\\Input');

		// Set the configuration
		$container->set('Joomla\\Registry\\Registry', $this->config, true, false);
		$container->alias('Registry', 'Joomla\\Registry\\Registry');
		$container->alias('config', 'Joomla\\Registry\\Registry');

		// Set the logger
		$container->set('Psr\\Log\\LoggerInterface', function()
		{
			// If a logger hasn't been set, use NullLogger
			return new NullLogger;
		}, true, false
		);
		$container->alias('LoggerInterface', 'Psr\\Log\\LoggerInterface');
		$container->alias('logger', 'Psr\\Log\\LoggerInterface');

		$this->initialise();
	}

	/**
	 * Method to close the application.
	 *
	 * @param   integer  $code  The exit code (optional; default is 0).
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 * @since   1.0
	 */
	public function close($code = 0)
	{
		exit($code);
	}

	/**
	 * Method to run the application routines.  Most likely you will want to instantiate a controller
	 * and execute it, or perform some sort of task directly.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	abstract protected function doExecute();

	/**
	 * Execute the application.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		// @event onBeforeExecute

		// Perform application routines.
		$this->doExecute();

		// @event onAfterExecute
	}

	/**
	 * Returns a property of the object or the default value if the property is not set.
	 *
	 * @param   string  $key      The name of the property.
	 * @param   mixed   $default  The default value (optional) if none is set.
	 *
	 * @return  mixed   The value of the configuration.
	 *
	 * @since   1.0
	 */
	public function get($key, $default = null)
	{
		return $this->getConfig()->get($key, $default);
	}

	/**
	 * Get the logger.
	 *
	 * @return  LoggerInterface
	 *
	 * @since   1.0
	 */
	public function getLogger()
	{
		return $this->getContainer()->get('Psr\\Log\\LoggerInterface');
	}

	/**
	 * Get the config of the application.
	 *
	 * @return  Joomla\Registry\Registry
	 *
	 * @since   2.0
	 */
	public function getConfig()
	{
		return $this->getContainer()->get('Joomla\\Registry\\Registry');
	}

	/**
	 * Get the input of the application.
	 *
	 * @return  Joomla\Input\Input
	 *
	 * @since   2.0
	 */
	public function getInput()
	{
		return $this->getContainer()->get('Joomla\\Input\\Input');
	}

	/**
	 * Get the DI container of the application.
	 *
	 * @return  Container
	 *
	 * @since   2.0
	 */
	public function getContainer()
	{
		if ($this->container === null)
		{
			$this->container = new Container;

			// Set the application
			$this->container->set('Joomla\\Application\\AbstractApplication', $this, false, true);
			$this->container->alias('AbstractApplication', 'Joomla\\Application\\AbstractApplication');
			$this->container->alias(get_class($this), 'Joomla\\Application\\AbstractApplication');
			$this->container->alias('app', 'Joomla\\Application\\AbstractApplication');
		}

		return $this->container;
	}

	/**
	 * Custom initialisation method.
	 *
	 * Called at the end of the AbstractApplication::__construct method.
	 * This is for developers to inject initialisation code for their application classes.
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 * @since   1.0
	 */
	protected function initialise()
	{
	}

	/**
	 * Modifies a property of the object, creating it if it does not already exist.
	 *
	 * @param   string  $key    The name of the property.
	 * @param   mixed   $value  The value of the property to set (optional).
	 *
	 * @return  mixed   Previous value of the property
	 *
	 * @since   1.0
	 */
	public function set($key, $value = null)
	{
		$previous = $this->getConfig()->get($key);
		$this->getConfig()->set($key, $value);

		return $previous;
	}

	/**
	 * Sets the configuration for the application.
	 *
	 * @param   Registry  $config  A registry object holding the configuration.
	 *
	 * @return  AbstractApplication  Returns itself to support chaining.
	 *
	 * @since   1.0
	 */
	public function setConfiguration(Registry $config)
	{
		$this->config = $config;

		$this->getContainer()->set('Joomla\\Registry\\Registry', $config);

		return $this;
	}

	/**
	 * Set the logger.
	 *
	 * @param   LoggerInterface  $logger  The logger.
	 *
	 * @return  AbstractApplication  Returns itself to support chaining.
	 *
	 * @since   1.0
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->getContainer()->set('Psr\\Log\\LoggerInterface', $logger);

		return $this;
	}
}

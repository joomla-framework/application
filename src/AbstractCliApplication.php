<?php
/**
 * Part of the Joomla Framework Application Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application;

use Joomla\Registry\Registry;
use Joomla\Input;
use Joomla\Application\Cli\CliOutput;

/**
 * Base class for a Joomla! command line application.
 *
 * @since  1.0
 */
abstract class AbstractCliApplication extends AbstractApplication
{
	/**
	 * @var    CliOutput  Output object
	 * @since  1.0
	 */
	protected $output;

	/**
	 * @var    CliInput   Cli Input object
	 * @since  1.2
	 */
	protected $cliInput;

	/**
	 * Class constructor.
	 *
	 * @param   Input\Cli  $input   An optional argument to provide dependency injection for the application's
	 *                              input object.  If the argument is a InputCli object that object will become
	 *                              the application's input object, otherwise a default input object is created.
	 * @param   Registry   $config  An optional argument to provide dependency injection for the application's
	 *                              config object.  If the argument is a Registry object that object will become
	 *                              the application's config object, otherwise a default config object is created.
	 *
	 * @param   CliOutput  $output  The output handler.
	 *
	 * @since   1.0
	 */
	public function __construct(Input\Cli $input = null, Registry $config = null, CliOutput $output = null)
	{
		// Close the application if we are not executed from the command line.
		// @codeCoverageIgnoreStart
		if (!defined('STDOUT') || !defined('STDIN') || !isset($_SERVER['argv']))
		{
			$this->close();
		}

		// @codeCoverageIgnoreEnd

		parent::__construct($input instanceof Input\Input ? $input : new Input\Cli, $config);

		// Set the execution datetime and timestamp;
		$this->set('execution.datetime', gmdate('Y-m-d H:i:s'));
		$this->set('execution.timestamp', time());

		// Set the current directory.
		$this->set('cwd', getcwd());

		$this->output = ($output instanceof CliOutput) ? $output : new Cli\Output\Stdout;

		// Set the input object.
		$this->cliInput = new Cli\CliInput;
	}

	/**
	 * Get an output object.
	 *
	 * @return  CliOutput
	 *
	 * @since   1.0
	 */
	public function getOutput()
	{
		return $this->output;
	}

	/**
	 * Get an cli input object.
	 *
	 * @return  CliInput
	 *
	 * @since   1.2
	 */
	public function getCliInput()
	{
		return $this->cliInput;
	}

	/**
	 * Write a string to standard output.
	 *
	 * @param   string   $text  The text to display.
	 * @param   boolean  $nl    True (default) to append a new line at the end of the output string.
	 *
	 * @return  AbstractCliApplication  Instance of $this to allow chaining.
	 *
	 * @codeCoverageIgnore
	 * @since   1.0
	 */
	public function out($text = '', $nl = true)
	{
		$this->output->out($text, $nl);

		return $this;
	}

	/**
	 * Get a value from standard input.
	 *
	 * @return  string  The input string from standard input.
	 *
	 * @codeCoverageIgnore
	 * @since   1.0
	 */
	public function in()
	{
		return $this->cliInput->in();
	}
}
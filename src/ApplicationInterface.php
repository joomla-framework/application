<?php
/**
 * Part of the Joomla Framework Application Package
 *
 * @copyright  Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application;

/**
 * Joomla Framework Application Interface
 *
 * @since  2.0.0-beta
 */
interface ApplicationInterface
{
	/**
	 * Method to close the application.
	 *
	 * @param   integer  $code  The exit code (optional; default is 0).
	 *
	 * @return  void
	 *
	 * @since   2.0.0-beta
	 */
	public function close($code = 0);

	/**
	 * Execute the application.
	 *
	 * @return  void
	 *
	 * @since   2.0.0-beta
	 */
	public function execute();
}

<?php
/**
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests\Stubs;

use Joomla\Controller\AbstractController;

class Controller extends AbstractController
{
	public function execute()
	{
		return 'Hello world!';
	}

	public function serialize()
	{
		return serialize('');
	}

	public function unserialize($serialized)
	{
		// Nothing to unserialize
	}
}

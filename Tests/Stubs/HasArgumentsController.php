<?php
/**
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests\Stubs;

use Joomla\Controller\ControllerInterface;

class HasArgumentsController implements ControllerInterface
{
	private $name;

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function execute()
	{
		return 'Hello ' . $this->name . '!';
	}

	public function serialize()
	{
		return serialize(
			[
				'name' => $this->name,
			]
		);
	}

	public function unserialize($serialized)
	{
		list($this->name) = unserialize($serialized);
	}
}

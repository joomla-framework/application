<?php
/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests;

use Joomla\Application\SessionAwareWebApplicationTrait;
use Joomla\Session\SessionInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Joomla\Application\SessionAwareWebApplicationTrait.
 */
class SessionAwareWebApplicationTraitTest extends TestCase
{
	/**
	 * @testdox  Tests a session object is correctly injected into the application and retrieved
	 *
	 * @covers  Joomla\Application\SessionAwareWebApplicationTrait::getSession
	 * @covers  Joomla\Application\SessionAwareWebApplicationTrait::setSession
	 */
	public function testSetSession()
	{
		$object = $this->getMockForTrait(SessionAwareWebApplicationTrait::class);
		$mockSession = $this->createMock(SessionInterface::class);

		$this->assertSame($object, $object->setSession($mockSession));
		$this->assertSame($mockSession, $object->getSession());
	}

	/**
	 * @testdox  Tests a RuntimeException is thrown when a Session object is not set to the application
	 *
	 * @covers  Joomla\Application\SessionAwareWebApplicationTrait::getSession
	 */
	public function testGetSessionForAnException()
	{
		$this->expectException(\RuntimeException::class);

		$object = $this->getMockForTrait(SessionAwareWebApplicationTrait::class);
		$object->getSession();
	}
}

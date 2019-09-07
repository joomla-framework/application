<?php
/**
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests;

use Joomla\Application\AbstractSessionAwareWebApplication;
use Joomla\Session\SessionInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Joomla\Application\AbstractSessionAwareWebApplication.
 */
class AbstractSessionAwareWebApplicationTest extends TestCase
{
	/**
	 * @testdox  Tests a session object is correctly injected into the application and retrieved
	 *
	 * @covers  Joomla\Application\AbstractSessionAwareWebApplication::getSession
	 * @covers  Joomla\Application\AbstractSessionAwareWebApplication::setSession
	 */
	public function testSetSession()
	{
		$object = $this->getMockForAbstractClass(AbstractSessionAwareWebApplication::class);
		$mockSession = $this->createMock(SessionInterface::class);

		$this->assertSame($object, $object->setSession($mockSession));
		$this->assertSame($mockSession, $object->getSession());
	}

	/**
	 * @testdox  Tests a RuntimeException is thrown when a Session object is not set to the application
	 *
	 * @covers  Joomla\Application\AbstractSessionAwareWebApplication::getSession
	 */
	public function testGetSessionForAnException()
	{
		$this->expectException(\RuntimeException::class);

		$object = $this->getMockForAbstractClass(AbstractSessionAwareWebApplication::class);
		$object->getSession();
	}
}

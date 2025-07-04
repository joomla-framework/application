<?php

/**
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests;

use Joomla\Application\SessionAwareWebApplicationTrait;
use Joomla\Input\Input;
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
     * @covers  Joomla\Application\SessionAwareWebApplicationTrait
     */
    public function testSetSession()
    {
        $object      = $this->getMockForTrait(SessionAwareWebApplicationTrait::class);
        $mockSession = $this->createMock(SessionInterface::class);

        $this->assertSame($object, $object->setSession($mockSession), 'The setSession method has a fluent interface.');
        $this->assertSame($mockSession, $object->getSession());
    }

    /**
     * @testdox  Tests a RuntimeException is thrown when a Session object is not set to the application
     *
     * @covers  Joomla\Application\SessionAwareWebApplicationTrait
     */
    public function testGetSessionForAnException()
    {
        $this->expectException(\RuntimeException::class);

        $object = $this->getMockForTrait(SessionAwareWebApplicationTrait::class);
        $object->getSession();
    }

    /**
     * @testdox  Tests the CSRF token can be checked from the `X-CSRF-Token` header
     *
     * @covers  Joomla\Application\SessionAwareWebApplicationTrait
     * @uses    Joomla\Application\AbstractApplication
     * @uses    Joomla\Application\AbstractWebApplication
     * @uses    Joomla\Application\WebApplication
     * @uses    Joomla\Application\Web\WebClient
     *
     * @backupGlobals enabled
     */
    public function testCheckTokenForHttpHeader()
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'token';

        $mockInput = new Input([]);

        $mockSession = $this->createMock(SessionInterface::class);
        $mockSession->expects($this->once())
            ->method('getToken')
            ->willReturn('testing');

        $mockSession->expects($this->once())
            ->method('hasToken')
            ->with('testing')
            ->willReturn(true);

        $object = $this->getMockForTrait(SessionAwareWebApplicationTrait::class);
        $object->setSession($mockSession);

        $object->expects($this->any())
            ->method('getInput')
            ->willReturn($mockInput);

        $this->assertTrue($object->checkToken());
    }

    /**
     * @testdox  Tests the CSRF token can be checked from the request body
     *
     * @covers  Joomla\Application\SessionAwareWebApplicationTrait
     * @uses    Joomla\Application\AbstractApplication
     * @uses    Joomla\Application\AbstractWebApplication
     * @uses    Joomla\Application\WebApplication
     * @uses    Joomla\Application\Web\WebClient
     *
     * @backupGlobals enabled
     */
    public function testCheckTokenForRequestBody()
    {
        $_POST['testing'] = 'token';

        $mockInput = new Input([]);

        $mockSession = $this->createMock(SessionInterface::class);
        $mockSession->expects($this->once())
            ->method('getToken')
            ->willReturn('testing');

        $mockSession->expects($this->once())
            ->method('hasToken')
            ->with('testing')
            ->willReturn(true);

        $object = $this->getMockForTrait(SessionAwareWebApplicationTrait::class);
        $object->setSession($mockSession);

        $object->expects($this->any())
            ->method('getInput')
            ->willReturn($mockInput);

        $this->assertTrue($object->checkToken());
    }

    /**
     * @testdox  Tests checking the CSRF token fails when it does not exist in the request
     *
     * @covers  Joomla\Application\SessionAwareWebApplicationTrait
     * @uses    Joomla\Application\AbstractApplication
     * @uses    Joomla\Application\AbstractWebApplication
     * @uses    Joomla\Application\WebApplication
     * @uses    Joomla\Application\Web\WebClient
     *
     * @backupGlobals enabled
     */
    public function testCheckTokenFailsWhenNotPresent()
    {
        $mockInput = new Input([]);

        $mockSession = $this->createMock(SessionInterface::class);
        $mockSession->expects($this->once())
            ->method('getToken')
            ->willReturn('testing');

        $mockSession->expects($this->never())
            ->method('hasToken');

        $object = $this->getMockForTrait(SessionAwareWebApplicationTrait::class);
        $object->setSession($mockSession);

        $object->expects($this->any())
            ->method('getInput')
            ->willReturn($mockInput);

        $this->assertFalse($object->checkToken());
    }
}

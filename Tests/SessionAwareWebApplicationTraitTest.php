<?php

/**
 * @copyright  (C) 2019 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests;

use Joomla\Application\AbstractApplication;
use Joomla\Application\AbstractWebApplication;
use Joomla\Application\SessionAwareWebApplicationTrait;
use Joomla\Application\Web\WebClient;
use Joomla\Application\WebApplication;
use Joomla\Input\Input;
use Joomla\Session\SessionInterface;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Joomla\Application\SessionAwareWebApplicationTrait.
 */
#[CoversTrait(SessionAwareWebApplicationTrait::class)]
#[UsesClass(AbstractApplication::class)]
#[UsesClass(AbstractWebApplication::class)]
#[UsesClass(WebApplication::class)]
#[UsesClass(WebClient::class)]
class SessionAwareWebApplicationTraitTest extends TestCase
{
    /**
     * Returns a lightweight object using SessionAwareWebApplicationTrait.
     *
     * The anonymous class provides a simple getInput() implementation,
     * making it suitable for tests that require a minimal trait consumer.
     *
     * @return  object  An object using SessionAwareWebApplicationTrait
     */
    private function getSessionAwareWebApplicationTrait()
    {
        return new class ()
        {
            use SessionAwareWebApplicationTrait;

            public function getInput(): Input
            {
                return new Input([]);
            }
        };
    }

    #[TestDox('Tests a session object is correctly injected into the application and retrieved')]
    public function testSetSession()
    {
        $object      = $this->getSessionAwareWebApplicationTrait();
        $mockSession = $this->createStub(SessionInterface::class);

        $this->assertSame($object, $object->setSession($mockSession), 'The setSession method has a fluent interface.');
        $this->assertSame($mockSession, $object->getSession());
    }

    #[TestDox('Tests a RuntimeException is thrown when a Session object is not set to the application')]
    public function testGetSessionForAnException()
    {
        $this->expectException(\RuntimeException::class);

        $object = $this->getSessionAwareWebApplicationTrait();
        $object->getSession();
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests the CSRF token can be checked from the `X-CSRF-Token` header')]
    public function testCheckTokenForHttpHeader()
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'token';

        $mockSession = $this->createMock(SessionInterface::class);
        $mockSession->expects($this->once())
            ->method('getToken')
            ->willReturn('testing');

        $mockSession->expects($this->once())
            ->method('hasToken')
            ->with('testing')
            ->willReturn(true);

        $object = $this->getSessionAwareWebApplicationTrait();
        $object->setSession($mockSession);

        $this->assertTrue($object->checkToken());
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests the CSRF token can be checked from the request body')]
    public function testCheckTokenForRequestBody()
    {
        $_POST['testing'] = 'token';

        $mockSession = $this->createMock(SessionInterface::class);
        $mockSession->expects($this->once())
            ->method('getToken')
            ->willReturn('testing');

        $mockSession->expects($this->once())
            ->method('hasToken')
            ->with('testing')
            ->willReturn(true);

        $object = $this->getSessionAwareWebApplicationTrait();
        $object->setSession($mockSession);

        $this->assertTrue($object->checkToken());
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests checking the CSRF token fails when it does not exist in the request')]
    public function testCheckTokenFailsWhenNotPresent()
    {
        $mockSession = $this->createMock(SessionInterface::class);
        $mockSession->expects($this->once())
            ->method('getToken')
            ->willReturn('testing');

        $mockSession->expects($this->never())
            ->method('hasToken');

        $object = $this->getSessionAwareWebApplicationTrait();
        $object->setSession($mockSession);

        $this->assertFalse($object->checkToken());
    }
}

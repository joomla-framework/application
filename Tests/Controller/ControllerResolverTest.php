<?php

/**
 * @copyright  (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests\Controller;

use Joomla\Application\Controller\ControllerResolver;
use Joomla\Application\Tests\Stubs\Controller;
use Joomla\Application\Tests\Stubs\HasArgumentsController;
use Joomla\Registry\Registry;
use Joomla\Router\ResolvedRoute;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Joomla\Application\Controller\ControllerResolver.
 */
class ControllerResolverTest extends TestCase
{
    /**
     * @testdox  Tests the resolver resolves a callable array
     *
     * @covers  Joomla\Application\Controller\ControllerResolver
     */
    public function testResolvingACallableArray()
    {
        $callable = (new ControllerResolver())->resolve(new ResolvedRoute([Registry::class, 'get'], [], '/'));

        $this->assertTrue(\is_callable($callable));
        $this->assertInstanceOf(Registry::class, $callable[0]);
    }

    /**
     * @testdox  Tests the resolver fails to resolve an array that is not callable
     *
     * @covers   Joomla\Application\Controller\ControllerResolver
     */
    public function testResolvingAnArrayFailsWhenNonCollable()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot resolve controller for URI `/`');

        (new ControllerResolver())->resolve(new ResolvedRoute([Registry::class, 'noWayThisWillEverExist'], [], '/'));
    }

    /**
     * @testdox  Tests the resolver resolves a callable array but fails instantiating a class with required arguments
     *
     * @covers   Joomla\Application\Controller\ControllerResolver
     */
    public function testResolvingACallableArrayFailsOnAClassWithRequiredArguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Controller `Joomla\Application\Tests\Stubs\HasArgumentsController` has required constructor arguments, cannot instantiate the class');

        (new ControllerResolver())->resolve(new ResolvedRoute([HasArgumentsController::class, 'execute'], [], '/'));
    }

    /**
     * @testdox  Tests the resolver resolves a callable object
     *
     * @covers  Joomla\Application\Controller\ControllerResolver
     */
    public function testResolvingACallableObject()
    {
        $controller = function () {
            return 'Hello world!';
        };

        $this->assertSame($controller, (new ControllerResolver())->resolve(new ResolvedRoute($controller, [], '/')));
    }

    /**
     * @testdox  Tests the resolver resolves a callable function
     *
     * @covers  Joomla\Application\Controller\ControllerResolver
     */
    public function testResolvingACallableFunction()
    {
        $this->assertSame('str_replace', (new ControllerResolver())->resolve(new ResolvedRoute('str_replace', [], '/')));
    }

    /**
     * @testdox  Tests the resolver resolves a ControllerInterface
     *
     * @covers  Joomla\Application\Controller\ControllerResolver
     */
    public function testResolvingAControllerInterface()
    {
        $callable = (new ControllerResolver())->resolve(new ResolvedRoute(Controller::class, [], '/'));

        $this->assertTrue(\is_callable($callable));
        $this->assertInstanceOf(Controller::class, $callable[0]);
    }

    /**
     * @testdox  Tests the resolver resolves a ControllerInterface but fails instantiating a class with required arguments
     *
     * @covers   Joomla\Application\Controller\ControllerResolver
     */
    public function testResolvingControllerInterfaceFailsOnAClassWithRequiredArguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Controller `Joomla\Application\Tests\Stubs\HasArgumentsController` has required constructor arguments, cannot instantiate the class');

        (new ControllerResolver())->resolve(new ResolvedRoute(HasArgumentsController::class, [], '/'));
    }
}

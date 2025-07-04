<?php

/**
 * @copyright  (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests\Controller;

use Joomla\Application\Controller\ContainerControllerResolver;
use Joomla\Application\Tests\Stubs\Controller;
use Joomla\Application\Tests\Stubs\HasArgumentsController;
use Joomla\DI\Container;
use Joomla\Router\ResolvedRoute;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Joomla\Application\Controller\ContainerControllerResolver.
 */
class ContainerControllerResolverTest extends TestCase
{
    /**
     * Resolver under test
     *
     * @var  ContainerControllerResolver
     */
    private $resolver;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $container = new Container();
        $container->set(
            Controller::class,
            function () {
                return new Controller();
            }
        );

        $this->resolver = new ContainerControllerResolver($container);
    }

    /**
     * @testdox  Tests the resolver resolves a ControllerInterface
     *
     * @covers  Joomla\Application\Controller\ContainerControllerResolver
     * @uses    Joomla\Application\Controller\ControllerResolver
     */
    public function testResolvingAControllerInterface()
    {
        $callable = $this->resolver->resolve(new ResolvedRoute(Controller::class, [], '/'));

        $this->assertTrue(\is_callable($callable));
        $this->assertInstanceOf(Controller::class, $callable[0]);
    }

    /**
     * @testdox  Tests the resolver resolves a ControllerInterface but fails instantiating a class with required arguments
     *
     * @covers  Joomla\Application\Controller\ContainerControllerResolver
     * @uses    Joomla\Application\Controller\ControllerResolver
     */
    public function testResolvingControllerInterfaceFailsOnAClassWithRequiredArguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Controller `Joomla\Application\Tests\Stubs\HasArgumentsController` has required constructor arguments, cannot instantiate the class');

        $this->resolver->resolve(new ResolvedRoute(HasArgumentsController::class, [], '/'));
    }
}

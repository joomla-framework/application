<?php

/**
 * @copyright  (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests\Controller;

use Joomla\Application\Controller\ControllerResolver;
use Joomla\Application\Tests\Stubs\Controller;
use Joomla\Application\Tests\Stubs\HasArgumentsController;
use Joomla\Application\Tests\Stubs\PlainController;
use Joomla\Application\Tests\Stubs\SameNameController;
use Joomla\Registry\Registry;
use Joomla\Router\ResolvedRoute;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Joomla\Application\Controller\ControllerResolver.
 */
#[CoversClass(ControllerResolver::class)]
class ControllerResolverTest extends TestCase
{
    #[TestDox('Tests the resolver resolves a callable array')]
    public function testResolvingACallableArray()
    {
        $callable = (new ControllerResolver())->resolve(new ResolvedRoute([Registry::class, 'get'], [], '/'));

        $this->assertIsCallable($callable);
        $this->assertInstanceOf(Registry::class, $callable[0]);
    }

    #[TestDox('Tests the resolver fails to resolve an array that is not callable')]
    public function testResolvingAnArrayFailsWhenNonCollable()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot resolve controller for URI `/`');

        (new ControllerResolver())->resolve(new ResolvedRoute([Registry::class, 'noWayThisWillEverExist'], [], '/'));
    }

    #[TestDox('Tests the resolver resolves a callable array but fails instantiating a class with required arguments')]
    public function testResolvingACallableArrayFailsOnAClassWithRequiredArguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Controller `Joomla\Application\Tests\Stubs\HasArgumentsController` has required constructor arguments, cannot instantiate the class');

        (new ControllerResolver())->resolve(new ResolvedRoute([HasArgumentsController::class, 'execute'], [], '/'));
    }

    #[TestDox('Tests the resolver resolves a callable object')]
    public function testResolvingACallableObject()
    {
        $controller = function () {
            return 'Hello world!';
        };

        $this->assertSame($controller, (new ControllerResolver())->resolve(new ResolvedRoute($controller, [], '/')));
    }

    #[TestDox('Tests the resolver resolves a callable function')]
    public function testResolvingACallableFunction()
    {
        $this->assertSame('str_replace', (new ControllerResolver())->resolve(new ResolvedRoute('str_replace', [], '/')));
    }

    #[TestDox('Tests the resolver resolves a ControllerInterface')]
    public function testResolvingAControllerInterface()
    {
        $callable = (new ControllerResolver())->resolve(new ResolvedRoute(Controller::class, [], '/'));

        $this->assertIsCallable($callable);
        $this->assertInstanceOf(Controller::class, $callable[0]);
    }

    #[TestDox('Tests the resolver resolves a ControllerInterface but fails instantiating a class with required arguments')]
    public function testResolvingControllerInterfaceFailsOnAClassWithRequiredArguments()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Controller `Joomla\Application\Tests\Stubs\HasArgumentsController` has required constructor arguments, cannot instantiate the class');

        (new ControllerResolver())->resolve(new ResolvedRoute(HasArgumentsController::class, [], '/'));
    }

    #[TestDox('Tests the resolver prefers a controller class over a function with the same name')]
    public function testResolvingAControllerClassBeforeAFunction()
    {
        // Load the file, which also declares the function
        $this->assertTrue(\class_exists(SameNameController::class));
        $this->assertTrue(\function_exists(SameNameController::class));

        $callable = (new ControllerResolver())->resolve(new ResolvedRoute(SameNameController::class, [], '/'));

        $this->assertIsArray($callable);
        $this->assertInstanceOf(SameNameController::class, $callable[0]);
    }

    #[IgnoreDeprecations]
    #[TestDox('Tests resolving a controller class which does not implement ControllerInterface is deprecated')]
    public function testResolvingAClassWithoutControllerInterfaceIsDeprecated()
    {
        $deprecations = [];

        set_error_handler(
            static function (int $errno, string $errstr) use (&$deprecations): bool {
                $deprecations[] = $errstr;

                return true;
            },
            E_USER_DEPRECATED
        );

        try {
            $callable = (new ControllerResolver())->resolve(new ResolvedRoute(PlainController::class, [], '/'));
        } finally {
            restore_error_handler();
        }

        $this->assertInstanceOf(PlainController::class, $callable[0]);
        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString('does not implement', $deprecations[0]);
    }

    #[IgnoreDeprecations]
    #[TestDox('Tests the resolver fails for a class without an execute() method')]
    public function testResolvingAClassWithoutExecuteMethodFails()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot resolve controller for URI `/`');

        (new ControllerResolver())->resolve(new ResolvedRoute(Registry::class, [], '/'));
    }

    #[TestDox('Tests the resolver fails for a controller which is neither a string, an array nor an object')]
    public function testResolvingANonStringFails()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot resolve controller for URI `/`');

        (new ControllerResolver())->resolve(new ResolvedRoute(null, [], '/'));
    }
}

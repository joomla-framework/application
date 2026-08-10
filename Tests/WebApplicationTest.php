<?php

/**
 * @copyright  (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license        GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests;

use Joomla\Application\AbstractApplication;
use Joomla\Application\AbstractWebApplication;
use Joomla\Application\Controller\ControllerResolverInterface;
use Joomla\Application\Web\WebClient;
use Joomla\Application\WebApplication;
use Joomla\Input\Input;
use Joomla\Router\ResolvedRoute;
use Joomla\Router\RouterInterface;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Joomla\Application\WebApplication.
 */
#[CoversClass(WebApplication::class)]
#[BackupGlobals(true)]
#[UsesClass(AbstractApplication::class)]
#[UsesClass(AbstractWebApplication::class)]
#[UsesClass(WebClient::class)]
class WebApplicationTest extends TestCase
{
    #[TestDox('Tests that the application is executed successfully.')]
    public function testExecute()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $controller = new class () {
            private $executed = false;

            public function __invoke()
            {
                $this->executed = true;
            }

            public function isExecuted(): bool
            {
                return $this->executed === true;
            }
        };

        $route = new ResolvedRoute($controller, [], '/');

        $router = $this->createMock(RouterInterface::class);

        $router->expects($this->once())
            ->method('parseRoute')
            ->willReturn($route);

        $resolver = $this->createMock(ControllerResolverInterface::class);

        $resolver->expects($this->once())
            ->method('resolve')
            ->with($route)
            ->willReturn($controller);

        $mockInput = new Input([]);

        (new WebApplication($resolver, $router, $mockInput))->execute();

        $this->assertTrue($controller->isExecuted());
    }
}

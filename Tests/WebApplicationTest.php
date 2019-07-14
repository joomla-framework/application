<?php
/**
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests;

use Joomla\Application\Controller\ControllerResolverInterface;
use Joomla\Application\WebApplication;
use Joomla\Input\Input;
use Joomla\Router\ResolvedRoute;
use Joomla\Router\Router;
use Joomla\Test\TestHelper;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Joomla\Application\WebApplication.
 *
 * @backupGlobals enabled
 */
class WebApplicationTest extends TestCase
{
	/**
	 * @testdox  Tests that the application is executed successfully.
	 *
	 * @covers  Joomla\Application\WebApplication::doExecute
	 */
	public function testExecute()
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$controller = new class
		{
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

		$router = $this->createMock(Router::class);

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

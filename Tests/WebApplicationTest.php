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
 */
class WebApplicationTest extends TestCase
{
	/**
	 * Enable or disable the backup and restoration of the $GLOBALS array.
	 * Overwrite this attribute in a child class of TestCase.
	 * Setting this attribute in setUp() has no effect!
	 *
	 * @var bool
	 */
	protected $backupGlobals = true;

	/**
	 * This method is called before the first test of this test class is run.
	 */
	public static function setUpBeforeClass()
	{
	}

	/**
	 * @testdox  Tests that the application is executed successfully.
	 *
	 * @covers  Joomla\Application\WebApplication::doExecute
	 */
	public function testExecute()
	{
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

		$router = $this->getMockBuilder(Router::class)
			->getMock();

		$router->expects($this->once())
			->method('parseRoute')
			->willReturn($route);

		$resolver = $this->getMockBuilder(ControllerResolverInterface::class)
			->getMock();

		$resolver->expects($this->once())
			->method('resolve')
			->with($route)
			->willReturn($controller);

		// For joomla/input 2.0
		$mockInput = new Input([]);

		// Mock the Input object internals
		$mockServerInput = new Input(
			[
				'REQUEST_METHOD' => 'GET',
			]
		);

		$inputInternals = [
			'server' => $mockServerInput,
		];

		TestHelper::setValue($mockInput, 'inputs', $inputInternals);

		// For joomla/input 1.0
		$_SERVER['REQUEST_METHOD'] = 'GET';

		(new WebApplication($resolver, $router, $mockInput))->execute();

		$this->assertTrue($controller->isExecuted());
	}
}

<?php
/**
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests;

/**
 * Test class for Joomla\Application\AbstractApplication.
 */
class AbstractApplicationTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @testdox  Tests the constructor creates default object instances
	 *
	 * @covers  Joomla\Application\AbstractApplication::__construct
	 */
	public function test__constructDefaultBehaviour()
	{
		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication');

		// Test attributes
		$this->assertAttributeInstanceOf('Joomla\Input\Input', 'input', $object);
		$this->assertAttributeInstanceOf('Joomla\Registry\Registry', 'config', $object);
		$this->assertAttributeInstanceOf('Joomla\DI\Container', 'container', $object);

		// Test if the container is filled correct with the applicatoin it belongs to
		$this->assertTrue($object->getContainer()->exists('Joomla\\Application\\AbstractApplication'));
		$this->assertInstanceOf('Joomla\Application\AbstractApplication', $object->getContainer()->get('Joomla\\Application\\AbstractApplication'));
		$this->assertTrue($object->getContainer()->exists('AbstractApplication'));
		$this->assertTrue($object->getContainer()->exists(get_class($object)));
		$this->assertTrue($object->getContainer()->exists('app'));

		// Test if the container is filled correct with an input
		$this->assertTrue($object->getContainer()->exists('Joomla\\Input\\Input'));
		$this->assertInstanceOf('Joomla\Input\Input', $object->getContainer()->get('Joomla\\Input\\Input'));
		$this->assertTrue($object->getContainer()->exists('Input'));
		$this->assertTrue($object->getContainer()->exists('input'));

		// Test if the container is filled correct with a config
		$this->assertTrue($object->getContainer()->exists('Joomla\\Registry\\Registry'));
		$this->assertInstanceOf('Joomla\Registry\Registry', $object->getContainer()->get('Joomla\\Registry\\Registry'));
		$this->assertTrue($object->getContainer()->exists('Registry'));
		$this->assertTrue($object->getContainer()->exists('config'));

		// Test if the container is filled correct with a logger
		$this->assertTrue($object->getContainer()->exists('Psr\\Log\\LoggerInterface'));
		$this->assertInstanceOf('Psr\Log\LoggerInterface', $object->getContainer()->get('Psr\\Log\\LoggerInterface'));
		$this->assertTrue($object->getContainer()->exists('LoggerInterface'));
		$this->assertTrue($object->getContainer()->exists('logger'));
	}

	/**
	 * @testdox  Tests the correct objects are stored when injected
	 *
	 * @covers  Joomla\Application\AbstractApplication::__construct
	 */
	public function test__constructDependencyInjection()
	{
		$mockInput  = $this->getMock('Joomla\Input\Input');
		$mockConfig = $this->getMock('Joomla\Registry\Registry');
		$object     = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication', array($mockInput, $mockConfig));

		$this->assertAttributeSame($mockInput, 'input', $object);
		$this->assertAttributeSame($mockConfig, 'config', $object);

		// Test if the container is filled correct with the input
		$this->assertTrue($object->getContainer()->exists('Joomla\\Input\\Input'));
		$this->assertEquals($mockInput, $object->getContainer()->get('Joomla\\Input\\Input'));
		$this->assertEquals($mockInput, $object->getContainer()->get('Input'));
		$this->assertEquals($mockInput, $object->getContainer()->get('input'));

		// Test if the container is filled correct with the config
		$this->assertTrue($object->getContainer()->exists('Joomla\\Registry\\Registry'));
		$this->assertEquals($mockConfig, $object->getContainer()->get('Joomla\\Registry\\Registry'));
		$this->assertEquals($mockConfig, $object->getContainer()->get('Registry'));
		$this->assertEquals($mockConfig, $object->getContainer()->get('config'));
	}

	/**
	 * @testdox  Tests that close() exits the application with the given code
	 *
	 * @covers  Joomla\Application\AbstractApplication::close
	 */
	public function testClose()
	{
		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication', array(), '', false, true, true, array('close'));
		$object->expects($this->any())
			->method('close')
			->willReturnArgument(0);

		$this->assertSame(3, $object->close(3));
	}

	/**
	 * @testdox  Tests that the application is executed successfully.
	 *
	 * @covers  Joomla\Application\AbstractApplication::execute
	 */
	public function testExecute()
	{
		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication');
		$object->expects($this->once())
			->method('doExecute');

		// execute() has no return, with our mock nothing should happen but ensuring that the mock's doExecute() stub is triggered
		$this->assertNull($object->execute());
	}

	/**
	 * @testdox  Tests that data is read from the application configuration successfully.
	 *
	 * @covers  Joomla\Application\AbstractApplication::get
	 */
	public function testGet()
	{
		$mockInput  = $this->getMock('Joomla\Input\Input');
		$mockConfig = $this->getMock('Joomla\Registry\Registry', array('get'), array(array('foo' => 'bar')), '', true, true, true, false, true);
		$object     = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication', array($mockInput, $mockConfig));

		$this->assertSame('bar', $object->get('foo', 'car'), 'Checks a known configuration setting is returned.');
		$this->assertSame('car', $object->get('goo', 'car'), 'Checks an unknown configuration setting returns the default.');
	}

	/**
	 * @testdox  Tests that a default LoggerInterface object is returned.
	 *
	 * @covers  Joomla\Application\AbstractApplication::getLogger
	 */
	public function testGetLogger()
	{
		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication');

		$this->assertInstanceOf('Psr\\Log\\NullLogger', $object->getLogger());
	}

	/**
	 * @testdox  Tests that a configuration is set and contains the data which is passed by the constructor.
	 *
	 * @covers  Joomla\Application\AbstractApplication::getConfig
	 */
	public function testGetConfig()
	{
		$mockConfig = $this->getMock('Joomla\Registry\Registry', array('get'), array(array('foo' => 'bar')), '', true, true, true, false, true);
		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication', array(null, $mockConfig));

		$this->assertInstanceOf('Joomla\\Registry\\Registry', $object->getConfig());
		$this->assertSame($mockConfig, $object->getConfig());
	}

	/**
	 * @testdox  Tests that a input is set and contains the data which is passed by the constructor.
	 *
	 * @covers  Joomla\Application\AbstractApplication::getInput
	 */
	public function testGetInput()
	{
		$mockInput  = $this->getMock('Joomla\Input\Input');
		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication', array($mockInput));

		$this->assertInstanceOf('Joomla\\Input\\Input', $object->getInput());
		$this->assertSame($mockInput, $object->getInput());
	}

	/**
	 * @testdox  Tests that a container exists and is filled correctly.
	 *
	 * @covers  Joomla\Application\AbstractApplication::getContainer
	 */
	public function testGetContainer()
	{
		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication');

		$container = $object->getContainer();
		$this->assertInstanceOf('Joomla\\DI\\Container', $container);

		// Tests if always the same instance is returned
		$this->assertSame($container, $object->getContainer());
	}

	/**
	 * @testdox  Tests that data is set to the application configuration successfully.
	 *
	 * @covers  Joomla\Application\AbstractApplication::set
	 * @uses    Joomla\Application\AbstractApplication::get
	 */
	public function testSet()
	{
		$mockInput  = $this->getMock('Joomla\Input\Input');
		$mockConfig = $this->getMock('Joomla\Registry\Registry', array('get', 'set'), array(array('foo' => 'bar')), '', true, true, true, false, true);
		$object     = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication', array($mockInput, $mockConfig));

		$this->assertEquals('bar', $object->set('foo', 'car'), 'Checks set returns the previous value.');
		$this->assertEquals('car', $object->get('foo'), 'Checks the new value has been set.');
	}

	/**
	 * @testdox  Tests that the application configuration is overwritten successfully.
	 *
	 * @covers  Joomla\Application\AbstractApplication::setConfiguration
	 */
	public function testSetConfiguration()
	{
		$object     = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication');
		$mockConfig = $this->getMock('Joomla\Registry\Registry');

		// First validate the two objects are different
		$this->assertAttributeNotSame($mockConfig, 'config', $object);

		// Now inject the config
		$object->setConfiguration($mockConfig);

		// Now the config objects should match
		$this->assertAttributeSame($mockConfig, 'config', $object);

		// Now the config in the container should match
		$this->assertSame($mockConfig, $object->getContainer()->get('Joomla\\Registry\\Registry'));
		$this->assertSame($mockConfig, $object->getContainer()->get('Registry'));
		$this->assertSame($mockConfig, $object->getContainer()->get('config'));
	}

	/**
	 * @testdox  Tests that a LoggerInterface object is correctly set to the application.
	 *
	 * @covers  Joomla\Application\AbstractApplication::setLogger
	 */
	public function testSetLogger()
	{
		$object     = $this->getMockForAbstractClass('Joomla\Application\AbstractApplication');
		$mockLogger = $this->getMockForAbstractClass('Psr\Log\AbstractLogger');

		$object->setLogger($mockLogger);

		$this->assertSame($mockLogger, $object->getLogger());

		// Now the logger in the container should match
		$this->assertSame($mockLogger, $object->getContainer()->get('Psr\\Log\\LoggerInterface'));
		$this->assertSame($mockLogger, $object->getContainer()->get('LoggerInterface'));
		$this->assertSame($mockLogger, $object->getContainer()->get('logger'));
	}
}

<?php
/**
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests;

/**
 * Test class for Joomla\Application\AbstractCliApplication.
 */
class AbstractCliApplicationTest extends CompatTestCase
{
	/**
	 * @testdox  Tests the constructor creates default object instances
	 *
	 * @covers   \Joomla\Application\AbstractCliApplication::__construct
	 */
	public function test__constructDefaultBehaviour()
	{
		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractCliApplication');

		// Validate default objects unique to the CLI application are created
		$this->assertInstanceOf('Joomla\Input\Cli', $object->input);
		$this->assertInstanceOf('Joomla\Application\Cli\Output\Stdout', $object->getOutput());
		$this->assertInstanceOf('Joomla\Application\Cli\CliInput', $object->getCliInput());
	}

	/**
	 * @testdox  Tests the correct objects are stored when injected
	 *
	 * @covers   \Joomla\Application\AbstractCliApplication::__construct
	 */
	public function test__constructDependencyInjection()
	{
		$mockInput = $this->getMockBuilder('Joomla\Input\Cli')->getMock();
		$mockConfig = $this->getMockBuilder('Joomla\Registry\Registry')->getMock();;
		$mockOutput = $this->getMockBuilder('Joomla\Application\Cli\Output\Stdout')->getMock();;
		$mockCliInput = $this->getMockBuilder('Joomla\Application\Cli\CliInput')->getMock();;

		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractCliApplication',
												 [$mockInput, $mockConfig, $mockOutput, $mockCliInput]);

		$this->assertSame($mockInput, $object->input);
		$this->assertSame($mockOutput, $object->getOutput());
		$this->assertSame($mockCliInput, $object->getCliInput());
	}

	/**
	 * @testdox  Tests that a default CliOutput object is returned.
	 *
	 * @covers   \Joomla\Application\AbstractCliApplication::getOutput
	 */
	public function testGetOutput()
	{
		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractCliApplication');

		$this->assertInstanceOf('Joomla\Application\Cli\Output\Stdout', $object->getOutput());
	}

	/**
	 * @testdox  Tests that a default CliInput object is returned.
	 *
	 * @covers   \Joomla\Application\AbstractCliApplication::getCliInput
	 */
	public function testGetCliInput()
	{
		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractCliApplication');

		$this->assertInstanceOf('Joomla\Application\Cli\CliInput', $object->getCliInput());
	}

	/**
	 * @testdox  Tests that the application sends output successfully.
	 *
	 * @covers   \Joomla\Application\AbstractCliApplication::out
	 */
	public function testOut()
	{
		$mockOutput = $this->getMockBuilder('Joomla\Application\Cli\Output\Stdout')->setMethods(['out'])->getMock();
		$mockOutput->expects($this->once())->method('out');

		$object = $this->getMockForAbstractClass('Joomla\Application\AbstractCliApplication', [null, null, $mockOutput]
		);

		$this->assertSame($object, $object->out('Testing'));
	}
}

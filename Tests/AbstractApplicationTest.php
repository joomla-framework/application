<?php

/**
 * @copyright  (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests;

use Joomla\Application\AbstractApplication;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use Joomla\Test\TestHelper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Test class for Joomla\Application\AbstractApplication.
 */
class AbstractApplicationTest extends TestCase
{
    /**
     * @testdox  Tests the constructor creates default object instances
     *
     * @covers  Joomla\Application\AbstractApplication
     * @uses    Joomla\Application\AbstractApplication
     * @uses    Joomla\Application\Web\WebClient
     */
    public function testConstructDefaultBehaviour()
    {
        $startTime      = \time();
        $startMicrotime = \microtime(true);

        $object = $this->getMockForAbstractClass(AbstractApplication::class);

        $this->assertInstanceOf(
            Registry::class,
            TestHelper::getValue($object, 'config'),
            'A default configuration Registry is created when one is not supplied'
        );

        // Validate default configuration data is written
        $executionDateTime = new \DateTime($object->get('execution.datetime'));

        $this->assertSame(\date('Y'), $executionDateTime->format('Y'));
        $this->assertGreaterThanOrEqual($startTime, $object->get('execution.timestamp'));
        $this->assertGreaterThanOrEqual($startMicrotime, $object->get('execution.microtimestamp'));
    }

    /**
     * @testdox  Tests the correct objects are stored when injected
     *
     * @covers  Joomla\Application\AbstractApplication
     */
    public function testConstructDependencyInjection()
    {
        $mockConfig = $this->createMock(Registry::class);
        $object     = $this->getMockForAbstractClass(AbstractApplication::class, [$mockConfig]);

        $this->assertSame(
            $mockConfig,
            TestHelper::getValue($object, 'config'),
            'A configuration Registry can be injected'
        );
    }

    /**
     * @testdox  Tests that \close() exits the application with the given code
     *
     * @covers  Joomla\Application\AbstractApplication
     */
    public function testClose()
    {
        $object = $this->getMockBuilder(AbstractApplication::class)
            ->onlyMethods(['close'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $object->expects($this->any())
            ->method('close')
            ->willReturnArgument(0);

        $this->assertSame(3, $object->close(3));
    }

    /**
     * @testdox  Tests that the application is executed successfully.
     *
     * @covers  Joomla\Application\AbstractApplication
     */
    public function testExecute()
    {
        $object = $this->getMockForAbstractClass(AbstractApplication::class);
        $object->expects($this->once())
            ->method('doExecute');

        $object->execute();
    }

    /**
     * @testdox  Tests that the application is executed successfully when an event dispatcher is registered.
     *
     * @covers  Joomla\Application\AbstractApplication
     * @uses    Joomla\Application\Event\ApplicationEvent
     */
    public function testExecuteWithEvents()
    {
        $dispatcher = $this->createMock(DispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $object = $this->getMockForAbstractClass(AbstractApplication::class);
        $object->expects($this->once())
            ->method('doExecute');

        $object->setDispatcher($dispatcher);

        $object->execute();
    }

    /**
     * @testdox  Tests that data is read from the application configuration successfully.
     *
     * @covers  Joomla\Application\AbstractApplication
     */
    public function testGet()
    {
        $mockConfig = $this->getMockBuilder(Registry::class)
            ->setConstructorArgs([['foo' => 'bar']])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $object = $this->getMockForAbstractClass(AbstractApplication::class, [$mockConfig]);

        $this->assertSame('bar', $object->get('foo', 'car'), 'Checks a known configuration setting is returned.');
        $this->assertSame('car', $object->get('goo', 'car'), 'Checks an unknown configuration setting returns the default.');
    }

    /**
     * @testdox  Tests that a default LoggerInterface object is returned.
     *
     * @covers  Joomla\Application\AbstractApplication
     */
    public function testGetLogger()
    {
        $object = $this->getMockForAbstractClass(AbstractApplication::class);

        $this->assertInstanceOf(NullLogger::class, $object->getLogger());
    }

    /**
     * @testdox  Tests that data is set to the application configuration successfully.
     *
     * @covers  Joomla\Application\AbstractApplication
     */
    public function testSet()
    {
        $mockConfig = $this->getMockBuilder(Registry::class)
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $object = $this->getMockForAbstractClass(AbstractApplication::class, [$mockConfig]);

        $this->assertNull($object->set('foo', 'car'), 'Checks set returns the previous value.');
        $this->assertEquals('car', $object->get('foo'), 'Checks the new value has been set.');
    }

    /**
     * @testdox  Tests that the application configuration is overwritten successfully.
     *
     * @covers  Joomla\Application\AbstractApplication
     */
    public function testSetConfiguration()
    {
        $object     = $this->getMockForAbstractClass(AbstractApplication::class);
        $mockConfig = $this->createMock(Registry::class);

        $this->assertSame($object, $object->setConfiguration($mockConfig), 'The setConfiguration method has a fluent interface');

        $this->assertSame(
            $mockConfig,
            TestHelper::getValue($object, 'config'),
            'The configuration Registry is overwritten'
        );
    }

    /**
     * @testdox  Tests that a LoggerInterface object is correctly set to the application.
     *
     * @covers  Joomla\Application\AbstractApplication
     */
    public function testSetLogger()
    {
        $object     = $this->getMockForAbstractClass(AbstractApplication::class);
        $mockLogger = $this->createMock(LoggerInterface::class);

        $object->setLogger($mockLogger);

        $this->assertSame(
            $mockLogger,
            TestHelper::getValue($object, 'logger'),
            'The logger is overwritten'
        );
    }
}

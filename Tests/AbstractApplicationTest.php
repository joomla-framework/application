<?php

/**
 * @copyright  (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests;

use Joomla\Application\AbstractApplication;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\Application\Web\WebClient;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;
use Joomla\Test\TestHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Test class for Joomla\Application\AbstractApplication.
 */
#[CoversClass(AbstractApplication::class)]
#[UsesClass(ApplicationEvent::class)]
#[UsesClass(WebClient::class)]
class AbstractApplicationTest extends TestCase
{
    /**
     * Returns a lightweight AbstractApplication instance for testing.
     *
     * The anonymous class forwards all constructor arguments to the parent
     * and provides an empty doExecute() implementation.
     *
     * @param   mixed  ...$args  Constructor arguments for AbstractApplication
     *
     * @return  AbstractApplication
     */
    private function getAbstractApplication(...$args): AbstractApplication
    {
        return new class (...$args) extends AbstractApplication
        {
            protected function doExecute()
            {
            }
        };
    }

    #[TestDox('Tests the constructor creates default object instances')]
    public function testConstructDefaultBehaviour()
    {
        $startTime      = \time();
        $startMicrotime = \microtime(true);

        $object = $this->getAbstractApplication();

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

    #[TestDox('Tests the correct objects are stored when injected')]
    public function testConstructDependencyInjection()
    {
        $mockConfig = $this->createMock(Registry::class);
        $object     = $this->getAbstractApplication($mockConfig);

        $this->assertSame(
            $mockConfig,
            TestHelper::getValue($object, 'config'),
            'A configuration Registry can be injected'
        );
    }

    #[TestDox('Tests that \close() exits the application with the given code')]
    public function testClose()
    {
        $object = $this->createMock(AbstractApplication::class);

        $object->expects($this->once())
            ->method('close')
            ->willReturnArgument(0);

        $this->assertSame(3, $object->close(3));
    }

    #[TestDox('Tests that the application is executed successfully.')]
    public function testExecute()
    {
        $object = $this->getMockBuilder(AbstractApplication::class)
            ->onlyMethods(['doExecute'])
            ->getMock();
        $object->expects($this->once())
            ->method('doExecute');

        $object->execute();
    }

    #[TestDox('Tests that the application is executed successfully when an event dispatcher is registered.')]
    public function testExecuteWithEvents()
    {
        $dispatcher = $this->createMock(DispatcherInterface::class);
        $dispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $object = $this->getMockBuilder(AbstractApplication::class)
            ->onlyMethods(['doExecute'])
            ->getMock();
        $object->expects($this->once())
            ->method('doExecute');

        $object->setDispatcher($dispatcher);

        $object->execute();
    }

    #[TestDox('Tests that data is read from the application configuration successfully.')]
    public function testGet()
    {
        $mockConfig = $this->getMockBuilder(Registry::class)
            ->setConstructorArgs([['foo' => 'bar']])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $object     = $this->getAbstractApplication($mockConfig);

        $this->assertSame('bar', $object->get('foo', 'car'), 'Checks a known configuration setting is returned.');
        $this->assertSame('car', $object->get('goo', 'car'), 'Checks an unknown configuration setting returns the default.');
    }

    #[TestDox('Tests that a default LoggerInterface object is returned.')]
    public function testGetLogger()
    {
        $object = $this->getAbstractApplication();

        $this->assertInstanceOf(NullLogger::class, $object->getLogger());
    }

    #[TestDox('Tests that data is set to the application configuration successfully.')]
    public function testSet()
    {
        $mockConfig = $this->getMockBuilder(Registry::class)
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $object     = $this->getAbstractApplication($mockConfig);

        $this->assertNull($object->set('foo', 'car'), 'Checks set returns the previous value.');
        $this->assertEquals('car', $object->get('foo'), 'Checks the new value has been set.');
    }

    #[TestDox('Tests that the application configuration is overwritten successfully.')]
    public function testSetConfiguration()
    {
        $object     = $this->getAbstractApplication();
        $mockConfig = $this->createMock(Registry::class);

        $this->assertSame($object, $object->setConfiguration($mockConfig), 'The setConfiguration method has a fluent interface');

        $this->assertSame(
            $mockConfig,
            TestHelper::getValue($object, 'config'),
            'The configuration Registry is overwritten'
        );
    }

    #[TestDox('Tests that a LoggerInterface object is correctly set to the application.')]
    public function testSetLogger()
    {
        $object = $this->getAbstractApplication();
        $mockLogger = $this->createMock(LoggerInterface::class);

        $object->setLogger($mockLogger);

        $this->assertSame(
            $mockLogger,
            TestHelper::getValue($object, 'logger'),
            'The logger is overwritten'
        );
    }
}

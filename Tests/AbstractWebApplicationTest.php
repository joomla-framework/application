<?php

/**
 * @copyright  (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license        GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests;

use Joomla\Application\AbstractApplication;
use Joomla\Application\AbstractWebApplication;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\Application\Web\WebClient;
use Joomla\Event\DispatcherInterface;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\Test\TestHelper;
use Laminas\Diactoros\Response\TextResponse;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Joomla\Application\AbstractWebApplication.
 */
#[CoversClass(AbstractWebApplication::class)]
#[UsesClass(AbstractApplication::class)]
#[UsesClass(ApplicationEvent::class)]
#[UsesClass(WebClient::class)]
class AbstractWebApplicationTest extends TestCase
{
    /**
     * Value for test host.
     *
     * @var  string
     */
    private const TEST_HTTP_HOST = 'mydomain.com';

    /**
     * Value for test user agent.
     *
     * @var  string
     */
    private const TEST_USER_AGENT = 'Mozilla/5.0';

    /**
     * Value for test user agent.
     *
     * @var  string
     */
    private const TEST_REQUEST_URI = '/index.php';

    /**
     * List of sent headers for inspection. array($string, $replace, $code).
     *
     * @var  array
     */
    private static $headers = [];

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        // Reset the $headers array
        self::$headers = [];

        parent::tearDown();
    }

    /**
     * Returns a lightweight AbstractWebApplication instance for testing.
     *
     * The anonymous class forwards all constructor arguments to the parent
     * and provides an empty doExecute() implementation.
     *
     * @param   mixed  ...$args  Constructor arguments for AbstractWebApplication
     *
     * @return  AbstractWebApplication
     */
    private function getAbstractWebApplication(...$args): AbstractWebApplication
    {
        return new class (...$args) extends AbstractWebApplication
        {
            protected function doExecute()
            {
            }
        };
    }

    /**
     * Data for detectRequestUri method.
     *
     * @return  array
     */
    public static function getDetectRequestUriData(): array
    {
        return [
            // HTTPS, PHP_SELF, REQUEST_URI, HTTP_HOST, SCRIPT_NAME, QUERY_STRING, (resulting uri)
            'HTTP connection with path in PHP_SELF and query string set in REQUEST_URI' => [
                null,
                '/j/index.php',
                '/j/index.php?foo=bar',
                'joom.la:3',
                '/j/index.php',
                '',
                'http://joom.la:3/j/index.php?foo=bar',
            ],

            'HTTPS connection with path in PHP_SELF and query string set in REQUEST_URI' => [
                'on',
                '/j/index.php',
                '/j/index.php?foo=bar',
                'joom.la:3',
                '/j/index.php',
                '',
                'https://joom.la:3/j/index.php?foo=bar',
            ],

            'HTTP connection with path in SCRIPT_NAME and no query string' => [
                null,
                '',
                '',
                'joom.la:3',
                '/j/index.php',
                '',
                'http://joom.la:3/j/index.php',
            ],

            'HTTP connection with path in SCRIPT_NAME and query string set in QUERY_STRING' => [
                null,
                '',
                '',
                'joom.la:3',
                '/j/index.php',
                'foo=bar',
                'http://joom.la:3/j/index.php?foo=bar',
            ],
        ];
    }

    /**
     * Data for testRedirectWithUrl method.
     *
     * @return  \Generator
     */
    public static function getRedirectData(): \Generator
    {
        // Note: url, (expected result)
        yield 'with_leading_slash' => ['/foo', 'http://' . self::TEST_HTTP_HOST . '/foo'];
        yield 'without_leading_slash' => ['foo', 'http://' . self::TEST_HTTP_HOST . '/foo'];
    }

    /**
     * Mock to send a header to the client.
     *
     * @param  string   $string    The header string.
     * @param  boolean  $replace   The optional replace parameter indicates whether the header should
     *                             replace a previous similar header, or add a second header of the same type.
     * @param  integer  $code      Forces the HTTP response code to the specified value. Note that
     *                             this parameter only has an effect if the string is not empty.
     *
     * @return  void
     */
    public static function mockHeader($string, $replace = true, $code = null)
    {
        self::$headers[] = [$string, $replace, $code];
    }

    #[TestDox('Tests the constructor creates default object instances')]
    public function testConstructDefaultBehaviour()
    {
        $object = $this->getAbstractWebApplication();

        // Validate default objects unique to the web application are created
        $this->assertInstanceOf(WebClient::class, $object->client);
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests the correct objects are stored when injected')]
    public function testConstructDependencyInjection()
    {
        $_SERVER['HTTP_HOST']   = self::TEST_HTTP_HOST;
        $_SERVER['REQUEST_URI'] = self::TEST_REQUEST_URI;
        $_SERVER['SCRIPT_NAME'] = self::TEST_REQUEST_URI;

        $mockInput  = new Input([]);
        $mockConfig = new Registry();
        $mockClient = new WebClient();

        $object = $this->getAbstractWebApplication($mockInput, $mockConfig, $mockClient);

        $this->assertSame($mockInput, $object->getInput());

        $this->assertSame(
            $mockConfig,
            TestHelper::getValue($object, 'config'),
            'A configuration Registry can be injected'
        );

        $this->assertSame($mockClient, $object->client);

        $this->assertEquals('http://' . self::TEST_HTTP_HOST, $object->get('uri.base.host'));
    }

    #[TestDox('Tests access to the input property is allowed')]
    public function testGetDeprecatedInputReadAccess()
    {
        $object = $this->getAbstractWebApplication();

        // Validate default objects unique to the web application are created
        $this->assertInstanceOf(Input::class, $object->getInput());
    }

    #[TestDox('Tests that the application is executed successfully.')]
    public function testExecute()
    {
        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->onlyMethods(['doExecute'])
            ->getMock();
        $object->expects($this->once())
            ->method('doExecute');

        $object->execute();

        $this->assertFalse($object->allowCache());

        $headers = $object->getHeaders();

        $this->assertSame(
            [
                'name'  => 'Content-Type',
                'value' => 'text/html; charset=utf-8',
            ],
            $headers[0]
        );

        $this->assertEmpty($object->getBody());
    }

    #[TestDox('Tests that the application is executed successfully when an event dispatcher is registered.')]
    public function testExecuteWithEvents()
    {
        $dispatcher = $this->createMock(DispatcherInterface::class);
        $dispatcher->expects($this->exactly(4))
            ->method('dispatch');

        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->onlyMethods(['doExecute'])
            ->getMock();
        $object->expects($this->once())
            ->method('doExecute');

        $object->setDispatcher($dispatcher);

        $object->execute();

        $this->assertFalse($object->allowCache());

        $headers = $object->getHeaders();

        $this->assertSame(
            [
                'name'  => 'Content-Type',
                'value' => 'text/html; charset=utf-8',
            ],
            $headers[0]
        );

        $this->assertEmpty($object->getBody());
    }

    #[TestDox('Tests that the application with compression enabled is executed successfully.')]
    public function testExecuteWithCompression()
    {
        // Verify compression is supported in this environment
        if (!(!\ini_get('zlib.output_compression') && (\ini_get('output_handler') != 'ob_gzhandler'))) {
            $this->markTestSkipped('Output compression is unsupported in this environment.');
        }

        $mockConfig = new Registry(['gzip' => true]);

        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->setConstructorArgs([null, $mockConfig])
            ->onlyMethods(['doExecute', 'compress'])
            ->getMock();
        $object->expects($this->once())
            ->method('doExecute');
        $object->expects($this->once())
            ->method('compress');

        $object->execute();

        $this->assertFalse($object->allowCache());

        $headers = $object->getHeaders();

        $this->assertSame(
            [
                'name'  => 'Content-Type',
                'value' => 'text/html; charset=utf-8',
            ],
            $headers[0]
        );

        $this->assertEmpty($object->getBody());
    }

    #[TestDox('Tests the \compress() method correctly compresses data with gzip encoding')]
    public function testCompressWithGzipEncoding()
    {
        $mockClient = new WebClient(null, 'gzip, deflate');

        // Mock a response.
        $response = new TextResponse(
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do
				eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim
				veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
				consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum
				dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident,
				sunt in culpa qui officia deserunt mollit anim id est laborum.'
        );
        $response = $response->withoutHeader('content-type');

        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->setConstructorArgs([null, null, $mockClient, $response])
            ->onlyMethods(['checkHeadersSent', 'doExecute'])
            ->getMock();

        $object->expects($this->once())
            ->method('checkHeadersSent')
            ->willReturn(false);

        TestHelper::invoke($object, 'compress');

        // Ensure that the compressed body is shorter than the raw body.
        $this->assertLessThan(
            \strlen($response->getBody()),
            $object->getBody()
        );

        // Ensure that the compression headers were set.
        $this->assertSame(
            [
                0 => ['name' => 'Content-Encoding', 'value' => 'gzip'],
                1 => ['name' => 'Vary', 'value' => 'Accept-Encoding'],
            ],
            $object->getHeaders()
        );
    }

    #[TestDox('Tests the compress() method correctly compresses data with deflate encoding')]
    public function testCompressWithDeflateEncoding()
    {
        $mockClient = new WebClient(null, 'deflate');

        // Mock a response.
        $response = new TextResponse(
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do
				eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim
				veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
				consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum
				dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident,
				sunt in culpa qui officia deserunt mollit anim id est laborum.'
        );
        $response = $response->withoutHeader('content-type');

        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->setConstructorArgs([null, null, $mockClient, $response])
            ->onlyMethods(['checkHeadersSent', 'doExecute'])
            ->getMock();

        $object->expects($this->once())
            ->method('checkHeadersSent')
            ->willReturn(false);

        TestHelper::invoke($object, 'compress');

        // Ensure that the compressed body is shorter than the raw body.
        $this->assertLessThan(
            \strlen($response->getBody()),
            \strlen($object->getBody())
        );

        // Ensure that the compression headers were set.
        $this->assertSame(
            [
                0 => ['name' => 'Content-Encoding', 'value' => 'deflate'],
                1 => ['name' => 'Vary', 'value' => 'Accept-Encoding'],
            ],
            $object->getHeaders()
        );
    }

    #[TestDox('Tests the \compress() method does not compress data when no encoding methods are supported')]
    public function testCompressWithNoAcceptEncodings()
    {
        $mockClient = new WebClient();

        // Mock a response.
        $response = new TextResponse(
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do
				eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim
				veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
				consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum
				dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident,
				sunt in culpa qui officia deserunt mollit anim id est laborum.'
        );
        $response = $response->withoutHeader('content-type');

        $object = $this->getAbstractWebApplication(null, null, $mockClient, $response);

        TestHelper::invoke($object, 'compress');

        // Ensure that the compressed body is shorter than the raw body.
        $this->assertSame(
            \strlen($response->getBody()),
            \strlen($object->getBody())
        );

        // Ensure that no compression headers were set.
        $this->assertEmpty($object->getHeaders());
    }

    #[TestDox('Tests the \compress() method does not compress data when the response headers have already been sent')]
    public function testCompressWithHeadersSent()
    {
        $mockClient = new WebClient(null, 'deflate');

        // Mock a response.
        $response = new TextResponse(
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do
				eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim
				veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
				consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum
				dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident,
				sunt in culpa qui officia deserunt mollit anim id est laborum.'
        );
        $response = $response->withoutHeader('content-type');

        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->setConstructorArgs([null, null, $mockClient, $response])
            ->onlyMethods(['checkHeadersSent', 'doExecute'])
            ->getMock();

        $object->expects($this->once())
            ->method('checkHeadersSent')
            ->willReturn(true);

        TestHelper::invoke($object, 'compress');

        // Ensure that the compressed body is shorter than the raw body.
        $this->assertSame(
            \strlen($response->getBody()),
            \strlen($object->getBody())
        );

        // Ensure that no compression headers were set.
        $this->assertEmpty($object->getHeaders());
    }

    #[TestDox('Tests the \compress() method does not compress data when the application does not support the client\'s encoding methods')]
    public function testCompressWithUnsupportedEncodings()
    {
        $mockClient = new WebClient();

        // Mock a response.
        $response = new TextResponse(
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do
				eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim
				veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
				consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum
				dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident,
				sunt in culpa qui officia deserunt mollit anim id est laborum.'
        );
        $response = $response->withoutHeader('content-type');

        $object = $this->getAbstractWebApplication(null, null, $mockClient, $response);

        TestHelper::invoke($object, 'compress');

        // Ensure that the compressed body is shorter than the raw body.
        $this->assertSame(
            \strlen($response->getBody()),
            \strlen($object->getBody())
        );

        // Ensure that no compression headers were set.
        $this->assertEmpty($object->getHeaders());
    }

    #[TestDox('Tests that the application sends the response successfully.')]
    public function testRespond()
    {
        $object = $this->getAbstractWebApplication();

        TestHelper::invoke($object, 'respond');

        $this->assertFalse($object->allowCache());

        $headers = $object->getHeaders();

        $this->assertSame(
            [
                'name'  => 'Content-Type',
                'value' => 'text/html; charset=utf-8',
            ],
            $headers[0]
        );

        $this->assertEmpty($object->getBody());
    }

    #[TestDox('Tests that the application sends the response successfully with allowed caching.')]
    public function testRespondWithAllowedCaching()
    {
        $modifiedDate = new \DateTime('now', new \DateTimeZone('GMT'));

        $object = $this->getAbstractWebApplication();
        $object->allowCache(true);
        $object->modifiedDate = $modifiedDate;

        TestHelper::invoke($object, 'respond');

        $this->assertTrue($object->allowCache());

        $headers = $object->getHeaders();

        $this->assertSame(
            [
                'name'  => 'Last-Modified',
                'value' => $modifiedDate->format('D, d M Y H:i:s') . ' GMT',
            ],
            $headers[2]
        );

        $this->assertEmpty($object->getBody());
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests that the application redirects successfully with the legacy behavior.')]
    public function testRedirectLegacyBehavior()
    {
        $_SERVER['HTTP_HOST']   = self::TEST_HTTP_HOST;
        $_SERVER['REQUEST_URI'] = self::TEST_REQUEST_URI;
        $_SERVER['SCRIPT_NAME'] = self::TEST_REQUEST_URI;

        $mockInput  = new Input([]);
        $mockConfig = new Registry();
        $mockClient = new WebClient();

        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->setConstructorArgs([$mockInput, $mockConfig, $mockClient])
            ->onlyMethods(['checkHeadersSent', 'close', 'header', 'doExecute'])
            ->getMock();

        $object->expects($this->once())
            ->method('close');
        $object->expects($this->exactly(2))
            ->method('checkHeadersSent')
            ->willReturn(false);
        $object->expects($this->exactly(7))
            ->method('header')
            ->willReturnCallback([$this, 'mockHeader']);

        $url = 'index.php';

        $date                 = new \DateTime('now', new \DateTimeZone('GMT'));
        $object->modifiedDate = $date;

        $object->redirect($url, 303);

        $this->assertSame(
            self::$headers,
            [
                ['HTTP/1.1 303 See other', true, 303],
                ['Location: http://' . self::TEST_HTTP_HOST . "/$url", true, null],
                ['Content-Type: text/html; charset=utf-8', true, null],
                ['Expires: Wed, 17 Aug 2005 00:00:00 GMT', true, null],
                ['Last-Modified: ' . $date->format('D, d M Y H:i:s e'), true, null],
                ['Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true, null],
                ['Pragma: no-cache', true, null],
            ]
        );
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests that the application redirects successfully.')]
    public function testRedirect()
    {
        $_SERVER['HTTP_HOST']   = self::TEST_HTTP_HOST;
        $_SERVER['REQUEST_URI'] = self::TEST_REQUEST_URI;
        $_SERVER['SCRIPT_NAME'] = self::TEST_REQUEST_URI;

        $mockInput  = new Input([]);
        $mockConfig = new Registry();
        $mockClient = new WebClient();

        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->setConstructorArgs([$mockInput, $mockConfig, $mockClient])
            ->onlyMethods(['checkHeadersSent', 'close', 'header', 'doExecute'])
            ->getMock();

        $object->expects($this->once())
            ->method('close');
        $object->expects($this->exactly(2))
            ->method('checkHeadersSent')
            ->willReturn(false);
        $object->expects($this->exactly(7))
            ->method('header')
            ->willReturnCallback([$this, 'mockHeader']);

        $url = 'index.php';

        $date                 = new \DateTime('now', new \DateTimeZone('GMT'));
        $object->modifiedDate = $date;
        $object->redirect($url);

        $this->assertSame(
            self::$headers,
            [
                ['HTTP/1.1 303 See other', true, 303],
                ['Location: http://' . self::TEST_HTTP_HOST . "/$url", true, null],
                ['Content-Type: text/html; charset=utf-8', true, null],
                ['Expires: Wed, 17 Aug 2005 00:00:00 GMT', true, null],
                ['Last-Modified: ' . $date->format('D, d M Y H:i:s e'), true, null],
                ['Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true, null],
                ['Pragma: no-cache', true, null],
            ]
        );
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests that the application redirects successfully when there is already a status code set.')]
    public function testRedirectWithExistingStatusCode()
    {
        $_SERVER['HTTP_HOST']   = self::TEST_HTTP_HOST;
        $_SERVER['REQUEST_URI'] = self::TEST_REQUEST_URI;
        $_SERVER['SCRIPT_NAME'] = self::TEST_REQUEST_URI;

        $mockInput  = new Input([]);
        $mockConfig = new Registry();
        $mockClient = new WebClient();

        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->setConstructorArgs([$mockInput, $mockConfig, $mockClient])
            ->onlyMethods(['checkHeadersSent', 'close', 'header', 'doExecute'])
            ->getMock();

        $object->expects($this->once())
            ->method('close');
        $object->expects($this->exactly(2))
            ->method('checkHeadersSent')
            ->willReturn(false);
        $object->expects($this->exactly(7))
            ->method('header')
            ->willReturnCallback([$this, 'mockHeader']);

        $url = 'index.php';

        $date                 = new \DateTime('now', new \DateTimeZone('GMT'));
        $object->modifiedDate = $date;
        $object->setHeader('status', 201);

        $object->redirect($url);

        $this->assertSame(
            self::$headers,
            [
                ['HTTP/1.1 303 See other', true, 303],
                ['Location: http://' . self::TEST_HTTP_HOST . "/$url", true, null],
                ['Content-Type: text/html; charset=utf-8', true, null],
                ['Expires: Wed, 17 Aug 2005 00:00:00 GMT', true, null],
                ['Last-Modified: ' . $date->format('D, d M Y H:i:s e'), true, null],
                ['Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true, null],
                ['Pragma: no-cache', true, null],
            ]
        );
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests that the application redirects and sends additional headers successfully.')]
    public function testRedirectWithAdditionalHeaders()
    {
        $_SERVER['HTTP_HOST']   = self::TEST_HTTP_HOST;
        $_SERVER['REQUEST_URI'] = self::TEST_REQUEST_URI;
        $_SERVER['SCRIPT_NAME'] = self::TEST_REQUEST_URI;

        $mockInput  = new Input([]);
        $mockConfig = new Registry();
        $mockClient = new WebClient();

        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->setConstructorArgs([$mockInput, $mockConfig, $mockClient])
            ->onlyMethods(['checkHeadersSent', 'close', 'header', 'doExecute'])
            ->getMock();

        $object->expects($this->once())
            ->method('close');
        $object->expects($this->exactly(2))
            ->method('checkHeadersSent')
            ->willReturn(false);
        $object->expects($this->exactly(7))
            ->method('header')
            ->willReturnCallback([$this, 'mockHeader']);

        $url = 'index.php';

        $date                 = new \DateTime('now', new \DateTimeZone('GMT'));
        $object->modifiedDate = $date;

        $object->redirect($url);

        $this->assertSame(
            self::$headers,
            [
                ['HTTP/1.1 303 See other', true, 303],
                ['Location: http://' . self::TEST_HTTP_HOST . "/$url", true, null],
                ['Content-Type: text/html; charset=utf-8', true, null],
                ['Expires: Wed, 17 Aug 2005 00:00:00 GMT', true, null],
                ['Last-Modified: ' . $date->format('D, d M Y H:i:s e'), true, null],
                ['Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true, null],
                ['Pragma: no-cache', true, null],
            ]
        );
    }

    #[BackupGlobals(true)]
    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    #[TestDox('Tests that the application redirects successfully when the headers have already been sent.')]
    public function testRedirectWithHeadersSent()
    {
        $_SERVER['HTTP_HOST']   = self::TEST_HTTP_HOST;
        $_SERVER['REQUEST_URI'] = self::TEST_REQUEST_URI;
        $_SERVER['SCRIPT_NAME'] = self::TEST_REQUEST_URI;

        $mockInput  = new Input([]);
        $mockConfig = new Registry();

        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->setConstructorArgs([$mockInput, $mockConfig])
            ->onlyMethods(['checkHeadersSent', 'close', 'doExecute'])
            ->getMock();

        $object->expects($this->once())
            ->method('close')
            ->willReturn(true);
        $object->expects($this->exactly(2))
            ->method('checkHeadersSent')
            ->willReturn(true);

        $url = 'index.php';

        // Capture the output for this test.
        \ob_start();
        $object->redirect('index.php');
        $buffer = \ob_get_clean();

        $this->assertSame(
            "<script>document.location.href=" . \json_encode(
                'http://' . self::TEST_HTTP_HOST . "/$url"
            ) . ";</script>\n",
            $buffer
        );
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests that the application redirects successfully with a JavaScript redirect.')]
    public function testRedirectWithJavascriptRedirect()
    {
        $_SERVER['HTTP_HOST']   = self::TEST_HTTP_HOST;
        $_SERVER['REQUEST_URI'] = self::TEST_REQUEST_URI;
        $_SERVER['SCRIPT_NAME'] = self::TEST_REQUEST_URI;

        $mockInput  = new Input([]);
        $mockConfig = new Registry();
        $mockClient = new WebClient('MSIE');

        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->setConstructorArgs([$mockInput, $mockConfig, $mockClient])
            ->onlyMethods(['checkHeadersSent', 'close', 'doExecute'])
            ->getMock();

        $object->expects($this->once())
            ->method('close');
        $object->expects($this->exactly(2))
            ->method('checkHeadersSent')
            ->willReturn(false);

        $url = 'http://j.org/index.php?phi=Φ';

        // Capture the output for this test.
        \ob_start();
        $object->redirect($url);
        $buffer = \ob_get_clean();

        $this->assertSame(
            '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8" />'
            . "<script>document.location.href=" . \json_encode($url) . ";</script></head><body></body></html>",
            \trim($buffer)
        );
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests that the application redirects successfully with the moved parameter set to true.')]
    public function testRedirectWithMoved()
    {
        $_SERVER['HTTP_HOST']   = self::TEST_HTTP_HOST;
        $_SERVER['REQUEST_URI'] = self::TEST_REQUEST_URI;
        $_SERVER['SCRIPT_NAME'] = self::TEST_REQUEST_URI;

        $mockInput  = new Input([]);
        $mockConfig = new Registry();
        $mockClient = new WebClient();

        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->setConstructorArgs([$mockInput, $mockConfig, $mockClient])
            ->onlyMethods(['checkHeadersSent', 'close', 'header', 'doExecute'])
            ->getMock();

        $object->expects($this->once())
            ->method('close');
        $object->expects($this->exactly(2))
            ->method('checkHeadersSent')
            ->willReturn(false);
        $object->expects($this->exactly(7))
            ->method('header')
            ->willReturnCallback([$this, 'mockHeader']);

        $url = 'http://j.org/index.php';

        $date                 = new \DateTime('now', new \DateTimeZone('GMT'));
        $object->modifiedDate = $date;

        $object->redirect($url, 301);

        $this->assertSame(
            self::$headers,
            [
                ['HTTP/1.1 301 Moved Permanently', true, 301],
                ['Location: ' . $url, true, null],
                ['Content-Type: text/html; charset=utf-8', true, null],
                ['Expires: Wed, 17 Aug 2005 00:00:00 GMT', true, null],
                ['Last-Modified: ' . $date->format('D, d M Y H:i:s e'), true, null],
                ['Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true, null],
                ['Pragma: no-cache', true, null],
            ]
        );
    }

    /**
     * @param  string  $url       The URL to redirect to
     * @param  string  $expected  The expected redirect URL
     */
    #[BackupGlobals(true)]
    #[DataProvider('getRedirectData')]
    #[TestDox('Tests that the application redirects successfully with the moved parameter set to true.')]
    public function testRedirectWithUrl(string $url, string $expected)
    {
        $_SERVER['HTTP_HOST']   = self::TEST_HTTP_HOST;
        $_SERVER['REQUEST_URI'] = self::TEST_REQUEST_URI;
        $_SERVER['SCRIPT_NAME'] = self::TEST_REQUEST_URI;

        $mockInput  = new Input([]);
        $mockConfig = new Registry();
        $mockClient = new WebClient();

        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->setConstructorArgs([$mockInput, $mockConfig, $mockClient])
            ->onlyMethods(['checkHeadersSent', 'close', 'header', 'doExecute'])
            ->getMock();

        $object->expects($this->once())
            ->method('close');
        $object->expects($this->exactly(2))
            ->method('checkHeadersSent')
            ->willReturn(false);
        $object->expects($this->exactly(7))
            ->method('header')
            ->willReturnCallback([$this, 'mockHeader']);

        $object->redirect($url);

        $this->assertSame(
            'Location: ' . $expected,
            self::$headers[1][0]
        );
    }

    #[TestDox('Tests the \allowCache() method returns the allowed cache state')]
    public function testAllowCache()
    {
        $object = $this->getAbstractWebApplication();

        $this->assertFalse($object->allowCache());
        $this->assertTrue($object->allowCache(true));
    }

    #[TestDox('Tests the \setHeader() method correctly sets and replaces a specified header')]
    public function testSetHeader()
    {
        $object = $this->getAbstractWebApplication();

        $object->setHeader('foo', 'bar');

        $this->assertSame(
            $object->getHeaders(),
            [
                ['name' => 'foo', 'value' => 'bar'],
            ]
        );

        $object->setHeader('foo', 'car', true);

        $this->assertSame(
            $object->getHeaders(),
            [
                ['name' => 'foo', 'value' => 'car'],
            ],
            'A header with the same name should be replaced.'
        );
    }

    #[TestDox('Tests the \clearHeaders() method resets the internal headers array')]
    public function testClearHeaders()
    {
        $object = $this->getAbstractWebApplication();
        $object->setHeader('foo', 'bar');
        $oldHeaders = $object->getHeaders();

        $this->assertSame($object, $object->clearHeaders());
        $this->assertNotSame($oldHeaders, $object->getHeaders());
    }

    #[TestDox('Tests the \sendHeaders() method correctly sends the response headers')]
    public function testSendHeaders()
    {
        $object = $this->getMockBuilder(AbstractWebApplication::class)
            ->onlyMethods(['checkHeadersSent', 'header', 'doExecute'])
            ->getMock();

        $object->expects($this->once())
            ->method('checkHeadersSent')
            ->willReturn(false);
        $object->expects($this->exactly(2))
            ->method('header')
            ->willReturnCallback([$this, 'mockHeader']);

        $object->setHeader('foo', 'bar');
        $object->setHeader('Status', 200);

        $this->assertSame($object, $object->sendHeaders());
        $this->assertSame(
            self::$headers,
            [
                ['foo: bar', true, null],
                ['HTTP/1.1 200 OK', true, 200],
            ]
        );
    }

    #[TestDox('Tests the \setBody() method correctly sets the response body')]
    public function testSetBody()
    {
        $object = $this->getAbstractWebApplication();

        $this->assertSame($object, $object->setBody('Testing'));
        $this->assertSame('Testing', $object->getBody());
    }

    #[TestDox('Tests the \prependBody() method correctly prepends content to the response body')]
    public function testPrependBody()
    {
        $object = $this->getAbstractWebApplication();

        $object->setBody('Testing');
        $this->assertSame($object, $object->prependBody('Pre-'));
        $this->assertSame('Pre-Testing', $object->getBody());
    }

    #[TestDox('Tests the \appendBody() method correctly appends content to the response body')]
    public function testAppendBody()
    {
        $object = $this->getAbstractWebApplication();

        $object->setBody('Testing');
        $this->assertSame($object, $object->appendBody(' Later'));
        $this->assertSame('Testing Later', $object->getBody());
    }

    #[TestDox('Tests the \getBody() method correctly retrieves the response body')]
    public function testGetBody()
    {
        $object = $this->getAbstractWebApplication();

        $this->assertSame('', $object->getBody(), 'Returns an empty string by default');
    }

    /**
     * @param  string|null  $https        Value for $_SERVER['HTTPS'] or null to not set it
     * @param  string       $phpSelf      Value for $_SERVER['PHP_SELF']
     * @param  string       $requestUri   Value for $_SERVER['REQUEST_URI']
     * @param  string       $httpHost     Value for $_SERVER['HTTP_HOST']
     * @param  string       $scriptName   Value for $_SERVER['SCRIPT_NAME']
     * @param  string       $queryString  Value for $_SERVER['QUERY_STRING']
     * @param  string       $expects      Expected full URI string
     */
    #[BackupGlobals(true)]
    #[DataProvider('getDetectRequestUriData')]
    #[TestDox('Tests that the application correctly detects the request URI based on the injected data')]
    public function testDetectRequestUri(
        ?string $https,
        string $phpSelf,
        string $requestUri,
        string $httpHost,
        string $scriptName,
        string $queryString,
        string $expects
    ) {
        $mockInput = new Input([]);

        $_SERVER['PHP_SELF']     = $phpSelf;
        $_SERVER['REQUEST_URI']  = $requestUri;
        $_SERVER['HTTP_HOST']    = $httpHost;
        $_SERVER['SCRIPT_NAME']  = $scriptName;
        $_SERVER['QUERY_STRING'] = $queryString;

        if ($https !== null) {
            $_SERVER['HTTPS'] = $https;
        }

        $object = $this->getAbstractWebApplication($mockInput);

        $this->assertSame(
            $expects,
            TestHelper::invoke($object, 'detectRequestUri')
        );
    }

    #[TestDox('Tests the system URIs are correctly loaded when a URI is set in the application configuration')]
    public function testLoadSystemUrisWithSiteUriSet()
    {
        $mockConfig = new Registry(['site_uri' => 'http://test.joomla.org/path/']);
        $object     = $this->getAbstractWebApplication(null, $mockConfig);

        TestHelper::invoke($object, 'loadSystemUris');

        $this->assertSame(
            'http://test.joomla.org/path/',
            $object->get('uri.base.full')
        );

        $this->assertSame(
            'http://test.joomla.org',
            $object->get('uri.base.host')
        );

        $this->assertSame(
            '/path/',
            $object->get('uri.base.path')
        );

        $this->assertSame(
            'http://test.joomla.org/path/media/',
            $object->get('uri.media.full')
        );

        $this->assertSame(
            '/path/media/',
            $object->get('uri.media.path')
        );
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests the system URIs are correctly loaded when a URI is passed into the method')]
    public function testLoadSystemUrisWithoutSiteUriSet()
    {
        $_SERVER['HTTP_HOST']   = self::TEST_HTTP_HOST;
        $_SERVER['SCRIPT_NAME'] = self::TEST_REQUEST_URI;

        $mockInput = new Input([]);
        $object    = $this->getAbstractWebApplication($mockInput);

        TestHelper::invoke($object, 'loadSystemUris', 'http://joom.la/application');

        $this->assertSame(
            'http://joom.la/',
            $object->get('uri.base.full')
        );

        $this->assertSame(
            'http://joom.la',
            $object->get('uri.base.host')
        );

        $this->assertSame(
            '/',
            $object->get('uri.base.path')
        );

        $this->assertSame(
            'http://joom.la/media/',
            $object->get('uri.media.full')
        );

        $this->assertSame(
            '/media/',
            $object->get('uri.media.path')
        );
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests the system URIs are correctly loaded when a media URI is set in the application configuration')]
    public function testLoadSystemUrisWithoutSiteUriWithMediaUriSet()
    {
        $_SERVER['HTTP_HOST']   = self::TEST_HTTP_HOST;
        $_SERVER['SCRIPT_NAME'] = self::TEST_REQUEST_URI;

        $mockInput  = new Input([]);
        $mockConfig = new Registry(['media_uri' => 'http://cdn.joomla.org/media/']);
        $object     = $this->getAbstractWebApplication($mockInput, $mockConfig);

        TestHelper::invoke($object, 'loadSystemUris', 'http://joom.la/application');

        $this->assertSame(
            'http://joom.la/',
            $object->get('uri.base.full')
        );

        $this->assertSame(
            'http://joom.la',
            $object->get('uri.base.host')
        );

        $this->assertSame(
            '/',
            $object->get('uri.base.path')
        );

        $this->assertSame(
            'http://cdn.joomla.org/media/',
            $object->get('uri.media.full')
        );

        $this->assertSame(
            'http://cdn.joomla.org/media/',
            $object->get('uri.media.path')
        );
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests the system URIs are correctly loaded when a relative media URI is set in the application configuration')]
    public function testLoadSystemUrisWithoutSiteUriWithRelativeMediaUriSet()
    {
        $_SERVER['HTTP_HOST']   = self::TEST_HTTP_HOST;
        $_SERVER['SCRIPT_NAME'] = self::TEST_REQUEST_URI;

        $mockInput  = new Input([]);
        $mockConfig = new Registry(['media_uri' => '/media/']);
        $object     = $this->getAbstractWebApplication($mockInput, $mockConfig);

        TestHelper::invoke($object, 'loadSystemUris', 'http://joom.la/application');

        $this->assertSame(
            'http://joom.la/',
            $object->get('uri.base.full')
        );

        $this->assertSame(
            'http://joom.la',
            $object->get('uri.base.host')
        );

        $this->assertSame(
            '/',
            $object->get('uri.base.path')
        );

        $this->assertSame(
            'http://joom.la/media/',
            $object->get('uri.media.full')
        );

        $this->assertSame(
            '/media/',
            $object->get('uri.media.path')
        );
    }

    #[BackupGlobals(true)]
    #[TestDox('Tests the application correctly detects if a SSL connection is active')]
    public function testisSslConnection()
    {
        $object = $this->getAbstractWebApplication();

        $this->assertFalse($object->isSslConnection());

        $object->getInput()->server->set('HTTPS', 'on');

        $this->assertTrue($object->isSslConnection());
    }

    #[TestDox('Tests the application correctly approves a valid HTTP Status Code')]
    public function testGetHttpStatusValue()
    {
        $object = $this->getAbstractWebApplication();

        $this->assertTrue($object->isValidHttpStatus(500));
    }

    #[TestDox('Tests the application correctly rejects a valid HTTP Status Code')]
    public function testInvalidHttpStatusValue()
    {
        $object = $this->getAbstractWebApplication();

        $this->assertFalse($object->isValidHttpStatus(460));
    }
}

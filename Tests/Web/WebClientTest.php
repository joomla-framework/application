<?php

/**
 * @copyright  (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Tests\Web;

use Joomla\Application\Web\WebClient;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Joomla\Application\Web\WebClient.
 *
 * @since         1.0.0
 *
 * @backupGlobals enabled
 */
class WebClientTest extends TestCase
{
    /**
     * Provides test data for user agent parsing.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public static function getUserAgentData()
    {
        // Platform, Mobile, Engine, Browser, Version, User Agent
        return [
            [
                WebClient::WINDOWS,
                false,
                WebClient::TRIDENT,
                WebClient::IE,
                '10.0',
                'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::TRIDENT,
                WebClient::IE,
                '9.0',
                'Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US))',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::EDGE,
                WebClient::EDGE,
                '14.14393',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.79 Safari/537.36 Edge/14.14393',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::TRIDENT,
                WebClient::IE,
                '8.0',
                'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 1.0.3705; .NET CLR 1.1.4322)',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::TRIDENT,
                WebClient::IE,
                '7.0b',
                'Mozilla/4.\0(compatible; MSIE 7.0b; Windows NT 6.0)',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::TRIDENT,
                WebClient::IE,
                '7.0b',
                'Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.1; Media Center PC 3.0; .NET CLR 1.0.3705; .NET CLR 1.1.4322; .NET CLR 2.0.50727; InfoPath.1)',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::TRIDENT,
                WebClient::IE,
                '7.0',
                'Mozilla/5.0 (Windows; U; MSIE 7.0; Windows NT 5.2)',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::TRIDENT,
                WebClient::IE,
                '6.1',
                'Mozilla/4.0 (compatible; MSIE 6.1; Windows XP)',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::TRIDENT,
                WebClient::IE,
                '6.0',
                'Mozilla/4.0 (compatible;MSIE 6.0;Windows 98;Q312461)',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::TRIDENT,
                WebClient::IE,
                '7.0',
                'Mozilla/4.0 (compatible; MSIE 7.0; AOL 9.6; AOLBuild 4340.128; Windows NT 5.1; Trident/4.0; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.648; .NET CLR 3.5.21022)',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::TRIDENT,
                WebClient::IE,
                '8.0',
                'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET4.0C; Maxthon 2.0)',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::TRIDENT,
                WebClient::IE,
                '7.0',
                'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; SlimBrowser)',
            ],
            [
                WebClient::MAC,
                false,
                WebClient::WEBKIT,
                WebClient::CHROME,
                '13.0.782.32',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_3) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.32 Safari/535.1',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::WEBKIT,
                WebClient::CHROME,
                '12.0.742.113',
                'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.113 Safari/534.30',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::BLINK,
                WebClient::CHROME,
                '54.0.2840.71',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36',
            ],
            [
                WebClient::LINUX,
                false,
                WebClient::WEBKIT,
                WebClient::CHROME,
                '12.0.742.112',
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/534.30 (KHTML, like Gecko) Ubuntu/10.04 Chromium/12.0.742.112 Chrome/12.0.742.112 Safari/534.30',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::WEBKIT,
                WebClient::CHROME,
                '15.0.864.0',
                'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.864.0 Safari/535.2',
            ],
            [
                WebClient::BLACKBERRY,
                true,
                WebClient::WEBKIT,
                WebClient::SAFARI,
                '6.0.0.546',
                'Mozilla/5.0 (BlackBerry; U; BlackBerry 9700; pt) AppleWebKit/534.8+ (KHTML, like Gecko) Version/6.0.0.546 Mobile Safari/534.8+',
            ],
            [
                WebClient::BLACKBERRY,
                true,
                WebClient::WEBKIT,
                '',
                '',
                'BlackBerry9700/5.0.0.862 Profile/MIDP-2.1 Configuration/CLDC-1.1 VendorID/120',
            ],
            [
                WebClient::ANDROIDTABLET,
                true,
                WebClient::WEBKIT,
                WebClient::SAFARI,
                '999.9',
                'Mozilla/5.0 (Linux; U; Android 2.3; en-us) AppleWebKit/999+ (KHTML, like Gecko) Safari/999.9',
            ],
            [
                WebClient::ANDROID,
                true,
                WebClient::WEBKIT,
                WebClient::SAFARI,
                '4.0',
                'Mozilla/5.0 (Linux; U; Android 2.2.1; en-ca; LG-P505R Build/FRG83) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
            ],
            [
                WebClient::ANDROIDTABLET,
                true,
                WebClient::WEBKIT,
                WebClient::SAFARI,
                '4.0',
                'Mozilla/5.0 (Linux; U; Android 3.0; en-us; Xoom Build/HRI39) AppleWebKit/534.13 (KHTML, like Gecko) Version/4.0 Safari/534.13',
            ],
            [
                WebClient::ANDROIDTABLET,
                true,
                WebClient::WEBKIT,
                WebClient::SAFARI,
                '4.0',
                'Mozilla/5.0 (Linux; U; Android 2.3.4; en-us; Silk/1.1.0-84) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1 Silk-Accelerated=false',
            ],
            [
                WebClient::ANDROIDTABLET,
                true,
                WebClient::GECKO,
                WebClient::FIREFOX,
                '12.0',
                ' Mozilla/5.0 (Android; Tablet; rv:12.0) Gecko/12.0 Firefox/12.0',
            ],
            [
                WebClient::ANDROIDTABLET,
                true,
                WebClient::PRESTO,
                WebClient::OPERA,
                '11.50',
                'Opera/9.80 (Android 3.2.1; Linux; Opera Tablet/ADR-1111101157; U; en) Presto/2.9.201 Version/11.50',
            ],
            [
                WebClient::IPAD,
                true,
                WebClient::WEBKIT,
                WebClient::SAFARI,
                '4.0.4',
                'Mozilla/5.\0(iPad; U; CPU iPhone OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B314 Safari/531.21.10gin_lib.cc',
            ],
            [
                WebClient::IPHONE,
                true,
                WebClient::WEBKIT,
                WebClient::SAFARI,
                '4.0.5',
                'Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_1 like Mac OS X; en-us) AppleWebKit/532.9 (KHTML, like Gecko) Version/4.0.5 Mobile/8B5097d Safari/6531.22.7',
            ],
            [
                WebClient::IPAD,
                true,
                WebClient::WEBKIT,
                WebClient::SAFARI,
                '4.0.4',
                'Mozilla/5.\0(iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B314 Safari/531.21.10gin_lib.cc',
            ],
            [
                WebClient::IPOD,
                true,
                WebClient::WEBKIT,
                WebClient::SAFARI,
                '4.0.4',
                'Mozilla/5.\0(iPod; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B314 Safari/531.21.10gin_lib.cc',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::WEBKIT,
                WebClient::SAFARI,
                '5.0.4',
                'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27',
            ],
            [
                WebClient::MAC,
                false,
                WebClient::WEBKIT,
                WebClient::SAFARI,
                '5.0.3',
                'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_5; ar) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::GECKO,
                WebClient::FIREFOX,
                '3.6.9',
                'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.9.2.9) Gecko/20100824 Firefox/3.6.9 ( .NET CLR 3.5.30729; .NET CLR 4.0.20506)',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::GECKO,
                WebClient::FIREFOX,
                '4.0b8pre',
                'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:2.0b8pre) Gecko/20101213 Firefox/4.0b8pre',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::GECKO,
                WebClient::FIREFOX,
                '5.0',
                'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:5.0) Gecko/20100101 Firefox/5.0',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::GECKO,
                WebClient::FIREFOX,
                '6.0',
                'Mozilla/5.0 (Windows NT 5.0; WOW64; rv:6.0) Gecko/20100101 Firefox/6.0',
            ],
            [
                WebClient::MAC,
                false,
                WebClient::GECKO,
                '',
                '',
                'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en; rv:1.9.2.14pre) Gecko/20101212 Camino/2.1a1pre (like Firefox/3.6.14pre)',
            ],
            [
                WebClient::LINUX,
                false,
                WebClient::KHTML,
                '',
                '',
                'Mozilla/5.0 (compatible; Konqueror/4.4; Linux 2.6.32-22-generic; X11; en_US) KHTML/4.4.3 (like Gecko) Kubuntu',
            ],
            [
                '',
                false,
                WebClient::AMAYA,
                '',
                '',
                'amaya/11.3.1 libwww/5.4.1',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::PRESTO,
                WebClient::OPERA,
                '8.50',
                'Mozilla/5.0 (Windows NT 5.1; U; de) Opera 8.50',
            ],
            [
                WebClient::WINDOWS,
                false,
                WebClient::BLINK,
                WebClient::EDG,
                '75.0.107.0',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3738.0 Safari/537.36 Edg/75.0.107.0',
            ],
        ];
    }

    /**
     * Provides test data for encoding parsing.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public static function getEncodingData()
    {
        // HTTP_ACCEPT_ENCODING, Supported Encodings
        return [
            ['gzip, deflate', ['gzip', 'deflate']],
            ['x-gzip, deflate', ['x-gzip', 'deflate']],
            ['gzip, x-gzip, deflate', ['gzip', 'x-gzip', 'deflate']],
            [' gzip, deflate ', ['gzip', 'deflate']],
            ['deflate, x-gzip', ['deflate', 'x-gzip']],
            ['goober , flasm', ['goober', 'flasm']],
            ['b2z, base64', ['b2z', 'base64']],
        ];
    }

    /**
     * Provides test data for language parsing.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public static function getLanguageData()
    {
        // HTTP_ACCEPT_LANGUAGE, Supported Language
        return [
            ['en-US, en-GB', ['en-US', 'en-GB']],
            ['fr-FR, de-DE', ['fr-FR', 'de-DE']],
            ['en-AU, en-CA, en-GB', ['en-AU', 'en-CA', 'en-GB']],
            [' nl-NL, de-DE ', ['nl-NL', 'de-DE']],
            ['en, nl-NL', ['en', 'nl-NL']],
            ['nerd , geek', ['nerd', 'geek']],
            ['xx-XX, xx', ['xx-XX', 'xx']],
        ];
    }

    /**
     * Provides test data for isRobot method.
     *
     * @return  array
     *
     * @since   1.0.0
     */
    public static function detectRobotData()
    {
        return [
            ['Googlebot/2.1 (+http://www.google.com/bot.html)', true],
            ['msnbot/1.0 (+http://search.msn.com/msnbot.htm)', true],
            ['Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)', true],
            ['Mozilla/5.0 (compatible; MJ12bot/v1.4.5; http://www.majestic12.co.uk/bot.php?+)', true],
            ['SimplePie/1.3.1 (Feed Parser; http://simplepie.org; Allow like Gecko) Build/20121030175911', true],
            ['Mozilla/4.0 compatible ZyBorg/1.0 (wn-14.zyborg@looksmart.net; http://www.WISEnutbot.com)', true],
            ['Mozilla/2.0 (compatible; Ask Jeeves/Teoma; +http://sp.ask.com/docs/about/tech_crawling.html)', true],
            [
                'Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7B405',
                false,
            ],
            [
                'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.168 Safari/535.19',
                false,
            ],
            [
                'Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
                true,
            ],
            [
                'BlackBerry8300/4.2.2 Profile/MIDP-2.0 Configuration/CLDC-1.1 VendorID/107 UP.Link/6.2.3.15.02011-10-16 20:20:17',
                false,
            ],
            [
                'IE 7 ? Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)2011-10-16 20:20:09',
                false,
            ],
        ];
    }

    /**
     * Setup for testing.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function setUp(): void
    {
        parent::setUp();

        $_SERVER['HTTP_HOST']          = 'mydomain.com';
        $_SERVER['HTTP_USER_AGENT']    = 'Mozilla/5.0';
        $_SERVER['HTTP_CUSTOM_HEADER'] = 'Client custom header';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==';
    }

    /**
     * Tests the WebClient::detectBrowser method.
     *
     * @param  string   $p   The expected platform.
     * @param  boolean  $m   The expected mobile result.
     * @param  string   $e   The expected engine.
     * @param  string   $b   The expected browser.
     * @param  string   $v   The expected browser version.
     * @param  string   $ua  The input user agent.
     *
     * @return  void
     *
     * @dataProvider getUserAgentData
     * @since        1.0.0
     * @covers       \Joomla\Application\Web\WebClient
     */
    public function testDetectBrowser($p, $m, $e, $b, $v, $ua)
    {
        $client = new WebClient($ua);

        // Test the assertions.
        $this->assertEquals($client->browser, $b, 'Browser detection failed');
        $this->assertEquals($client->browserVersion, $v, 'Version detection failed');
    }

    /**
     * Tests the WebClient::detectheaders method.
     *
     * @return  void
     *
     * @since   1.0.0
     * @covers  \Joomla\Application\Web\WebClient
     */
    public function testDetectHeaders()
    {
        $expected = [
            'Host'          => 'mydomain.com',
            'User-Agent'    => 'Mozilla/5.0',
            'Custom-Header' => 'Client custom header',
            'Authorization' => 'Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==',
        ];

        $this->assertEquals((new WebClient())->headers, $expected, 'Headers detection failed');
    }

    /**
     * Tests the WebClient::detectEncoding method.
     *
     * @param  string  $ae  The input accept encoding.
     * @param  array   $e   The expected array of encodings.
     *
     * @return  void
     *
     * @dataProvider getEncodingData
     * @since        1.0.0
     * @covers       \Joomla\Application\Web\WebClient
     */
    public function testDetectEncoding($ae, $e)
    {
        $client = new WebClient(null, $ae);

        // Test the assertions.
        $this->assertEquals($client->encodings, $e, 'Encoding detection failed');
    }

    /**
     * Tests the WebClient::detectEngine method.
     *
     * @param  string   $p   The expected platform.
     * @param  boolean  $m   The expected mobile result.
     * @param  string   $e   The expected engine.
     * @param  string   $b   The expected browser.
     * @param  string   $v   The expected browser version.
     * @param  string   $ua  The input user agent.
     *
     * @return  void
     *
     * @dataProvider getUserAgentData
     * @since        1.0.0
     * @covers       \Joomla\Application\Web\WebClient
     */
    public function testDetectEngine($p, $m, $e, $b, $v, $ua)
    {
        $client = new WebClient($ua);

        // Test the assertion.
        $this->assertEquals($client->engine, $e, 'Engine detection failed.');
    }

    /**
     * Tests the WebClient::detectLanguage method.
     *
     * @param  string  $al  The input accept language.
     * @param  array   $l   The expected array of languages.
     *
     * @return  void
     *
     * @dataProvider getLanguageData
     * @since        1.0.0
     * @covers       \Joomla\Application\Web\WebClient
     */
    public function testDetectLanguage($al, $l)
    {
        $client = new WebClient(null, null, $al);

        // Test the assertions.
        $this->assertEquals($client->languages, $l, 'Language detection failed');
    }

    /**
     * Tests the WebClient::detectPlatform method.
     *
     * @param  string   $p   The expected platform.
     * @param  boolean  $m   The expected mobile result.
     * @param  string   $e   The expected engine.
     * @param  string   $b   The expected browser.
     * @param  string   $v   The expected browser version.
     * @param  string   $ua  The input user agent.
     *
     * @return  void
     *
     * @dataProvider getUserAgentData
     * @since        1.0.0
     * @covers       \Joomla\Application\Web\WebClient
     */
    public function testDetectPlatform($p, $m, $e, $b, $v, $ua)
    {
        $client = new WebClient($ua);

        // Test the assertions.
        $this->assertEquals($client->mobile, $m, 'Mobile detection failed.');
        $this->assertEquals($client->platform, $p, 'Platform detection failed.');
    }

    /**
     * Tests the WebClient::detectRobot method.
     *
     * @param  string   $userAgent  The user agent
     * @param  boolean  $expected   The expected results of the function
     *
     * @return  void
     *
     * @dataProvider detectRobotData
     * @since        1.0.0
     * @covers       \Joomla\Application\Web\WebClient
     */
    public function testDetectRobot($userAgent, $expected)
    {
        $client = new WebClient($userAgent);

        // Test the assertions.
        $this->assertEquals($client->robot, $expected, 'Robot detection failed');
    }
}

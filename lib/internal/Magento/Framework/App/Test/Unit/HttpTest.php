<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\App\Test\Unit;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\SetupInfo;
use Magento\Framework\App\Bootstrap;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HttpTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var \Magento\Framework\App\Http
     */
    protected $http;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontControllerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $areaListMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configLoaderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $filesystemMock;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $cookieReaderMock = $this->getMockBuilder(\Magento\Framework\Stdlib\Cookie\CookieReaderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $routeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Route\ConfigInterface\Proxy::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pathInfoProcessorMock = $this->getMockBuilder(\Magento\Framework\App\Request\PathInfoProcessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $converterMock = $this->getMockBuilder(\Magento\Framework\Stdlib\StringUtils::class)
            ->disableOriginalConstructor()
            ->setMethods(['cleanString'])
            ->getMock();
        $objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->setConstructorArgs([
                'cookieReader' => $cookieReaderMock,
                'converter' => $converterMock,
                'routeConfig' => $routeConfigMock,
                'pathInfoProcessor' => $pathInfoProcessorMock,
                'objectManager' => $objectManagerMock
            ])
            ->setMethods(['getFrontName', 'isHead'])
            ->getMock();
        $this->areaListMock = $this->getMockBuilder(\Magento\Framework\App\AreaList::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCodeByFrontName'])
            ->getMock();
        $this->configLoaderMock = $this->getMockBuilder(\Magento\Framework\App\ObjectManager\ConfigLoader::class)
            ->disableOriginalConstructor()
            ->setMethods(['load'])
            ->getMock();
        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->responseMock = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $this->frontControllerMock = $this->getMockBuilder(\Magento\Framework\App\FrontControllerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['dispatch'])
            ->getMock();
        $this->eventManagerMock = $this->getMockBuilder(\Magento\Framework\Event\Manager::class)
            ->disableOriginalConstructor()
            ->setMethods(['dispatch'])
            ->getMock();
        $this->filesystemMock = $this->createMock(\Magento\Framework\Filesystem::class);

        $this->http = $this->objectManager->getObject(
            \Magento\Framework\App\Http::class,
            [
                'objectManager' => $this->objectManagerMock,
                'eventManager' => $this->eventManagerMock,
                'areaList' => $this->areaListMock,
                'request' => $this->requestMock,
                'response' => $this->responseMock,
                'configLoader' => $this->configLoaderMock,
                'filesystem' => $this->filesystemMock,
            ]
        );
    }

    /**
     * Asserts mock objects with methods that are expected to be called when http->launch() is invoked.
     */
    private function setUpLaunch()
    {
        $frontName = 'frontName';
        $areaCode = 'areaCode';
        $this->requestMock->expects($this->once())
            ->method('getFrontName')
            ->willReturn($frontName);
        $this->areaListMock->expects($this->once())
            ->method('getCodeByFrontName')
            ->with($frontName)
            ->willReturn($areaCode);
        $this->configLoaderMock->expects($this->once())
            ->method('load')
            ->with($areaCode)
            ->willReturn([]);
        $this->objectManagerMock->expects($this->once())->method('configure')->with([]);
        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(\Magento\Framework\App\FrontControllerInterface::class)
            ->will($this->returnValue($this->frontControllerMock));
        $this->frontControllerMock->expects($this->once())
            ->method('dispatch')
            ->with($this->requestMock)
            ->willReturn($this->responseMock);
    }

    public function testLaunchSuccess()
    {
        $this->setUpLaunch();
        $this->requestMock->expects($this->once())
            ->method('isHead')
            ->willReturn(false);
        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with(
                'controller_front_send_response_before',
                ['request' => $this->requestMock, 'response' => $this->responseMock]
            );
        $this->assertSame($this->responseMock, $this->http->launch());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Message
     */
    public function testLaunchException()
    {
        $this->setUpLaunch();
        $this->frontControllerMock->expects($this->once())
            ->method('dispatch')
            ->with($this->requestMock)
            ->willThrowException(
                new \Exception('Message')
            );
        $this->http->launch();
    }

    /**
     * Test that HEAD requests lead to an empty body and a Content-Length header matching the original body size.
     * @dataProvider dataProviderForTestLaunchHeadRequest
     * @param string $body
     * @param int $expectedLength
     */
    public function testLaunchHeadRequest($body, $expectedLength)
    {
        $this->setUpLaunch();
        $this->requestMock->expects($this->once())
            ->method('isHead')
            ->willReturn(true);
        $this->responseMock->expects($this->once())
            ->method('getHttpResponseCode')
            ->willReturn(200);
        $this->responseMock->expects($this->once())
            ->method('getContent')
            ->willReturn($body);
        $this->responseMock->expects($this->once())
            ->method('clearBody')
            ->willReturn($this->responseMock);
        $this->responseMock->expects($this->once())
            ->method('setHeader')
            ->with('Content-Length', $expectedLength)
            ->willReturn($this->responseMock);
        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with(
                'controller_front_send_response_before',
                ['request' => $this->requestMock, 'response' => $this->responseMock]
            );
        $this->assertSame($this->responseMock, $this->http->launch());
    }

    /**
     * Different test content for responseMock with their expected lengths in bytes.
     * @return array
     */
    public function dataProviderForTestLaunchHeadRequest(): array
    {
        return [
            [
                "<html><head></head><body>Test</body></html>",                // Ascii text
                43                                                            // Expected Content-Length
            ],
            [
                "<html><head></head><body>部落格</body></html>",               // Multi-byte characters
                48                                                            // Expected Content-Length
            ],
            [
                "<html><head></head><body>\0</body></html>",     // Null byte
                40                                                            // Expected Content-Length
            ],
            [
                "<html><head></head>خرید<body></body></html>",                // LTR text
                47                                                            // Expected Content-Length
            ]
        ];
    }

    public function testHandleDeveloperModeNotInstalled()
    {
        $dir = $this->getMockForAbstractClass(\Magento\Framework\Filesystem\Directory\ReadInterface::class);
        $dir->expects($this->once())
            ->method('getAbsolutePath')
            ->willReturn(__DIR__);
        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryRead')
            ->with(DirectoryList::ROOT)
            ->willReturn($dir);
        $this->responseMock->expects($this->once())
            ->method('setRedirect')
            ->with('/_files/');
        $this->responseMock->expects($this->once())
            ->method('sendHeaders');
        $bootstrap = $this->getBootstrapNotInstalled();
        $bootstrap->expects($this->once())
            ->method('getParams')
            ->willReturn(
                [
                    'SCRIPT_NAME' => '/index.php',
                    'DOCUMENT_ROOT' => __DIR__,
                    'SCRIPT_FILENAME' => __DIR__ . '/index.php',
                    SetupInfo::PARAM_NOT_INSTALLED_URL_PATH => '_files',
                ]
            );
        $this->assertTrue($this->http->catchException($bootstrap, new \Exception('Test Message')));
    }

    public function testHandleDeveloperMode()
    {
        $this->filesystemMock->expects($this->once())
            ->method('getDirectoryRead')
            ->will($this->throwException(new \Exception('strange error')));
        $this->responseMock->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(500);
        $this->responseMock->expects($this->once())
            ->method('setHeader')
            ->with('Content-Type', 'text/plain');
        $constraint = new \PHPUnit\Framework\Constraint\StringStartsWith('1 exception(s):');
        $this->responseMock->expects($this->once())
            ->method('setBody')
            ->with($constraint);
        $this->responseMock->expects($this->once())
            ->method('sendResponse');
        $bootstrap = $this->getBootstrapNotInstalled();
        $bootstrap->expects($this->once())
            ->method('getParams')
            ->willReturn(
                ['DOCUMENT_ROOT' => 'something', 'SCRIPT_FILENAME' => 'something/else']
            );
        $this->assertTrue($this->http->catchException($bootstrap, new \Exception('Test')));
    }

    public function testCatchExceptionSessionException()
    {
        $this->responseMock->expects($this->once())
            ->method('setRedirect');
        $this->responseMock->expects($this->once())
            ->method('sendHeaders');
        $bootstrap = $this->createMock(\Magento\Framework\App\Bootstrap::class);
        $bootstrap->expects($this->once())
            ->method('isDeveloperMode')
            ->willReturn(false);
        $this->assertTrue($this->http->catchException(
            $bootstrap,
            new \Magento\Framework\Exception\SessionException(new \Magento\Framework\Phrase('Test'))
        ));
    }

    /**
     * Prepares a mock of bootstrap in "not installed" state
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getBootstrapNotInstalled()
    {
        $bootstrap = $this->createMock(\Magento\Framework\App\Bootstrap::class);
        $bootstrap->expects($this->once())
            ->method('isDeveloperMode')
            ->willReturn(true);
        $bootstrap->expects($this->once())
            ->method('getErrorCode')
            ->willReturn(Bootstrap::ERR_IS_INSTALLED);
        return $bootstrap;
    }
}

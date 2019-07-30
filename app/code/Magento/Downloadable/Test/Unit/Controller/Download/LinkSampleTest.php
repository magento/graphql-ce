<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Downloadable\Test\Unit\Controller\Download;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Unit tests for \Magento\Downloadable\Controller\Download\LinkSample.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LinkSampleTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Downloadable\Controller\Download\LinkSample */
    protected $linkSample;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\ResponseInterface
     */
    protected $response;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\ObjectManager\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Downloadable\Helper\Data
     */
    protected $helperData;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Downloadable\Helper\Download
     */
    protected $downloadHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Catalog\Model\Product
     */
    protected $product;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var \Magento\Catalog\Model\Product\SalabilityChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    private $salabilityCheckerMock;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->response = $this->createPartialMock(
            \Magento\Framework\App\ResponseInterface::class,
            [
                'setHttpResponseCode',
                'clearBody',
                'sendHeaders',
                'sendResponse',
                'setHeader',
                'setRedirect'
            ]
        );

        $this->helperData = $this->createPartialMock(
            \Magento\Downloadable\Helper\Data::class,
            ['getIsShareable']
        );
        $this->downloadHelper = $this->createPartialMock(
            \Magento\Downloadable\Helper\Download::class,
            [
                'setResource',
                'getFilename',
                'getContentType',
                'getFileSize',
                'getContentDisposition',
                'output'
            ]
        );
        $this->product = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            [
                '_wakeup',
                'load',
                'getId',
                'getProductUrl',
                'getName'
            ]
        );
        $this->messageManager = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $this->redirect = $this->createMock(\Magento\Framework\App\Response\RedirectInterface::class);
        $this->urlInterface = $this->createMock(\Magento\Framework\UrlInterface::class);
        $this->salabilityCheckerMock = $this->createMock(\Magento\Catalog\Model\Product\SalabilityChecker::class);
        $this->objectManager = $this->createPartialMock(
            \Magento\Framework\ObjectManager\ObjectManager::class,
            ['create', 'get']
        );
        $this->linkSample = $this->objectManagerHelper->getObject(
            \Magento\Downloadable\Controller\Download\LinkSample::class,
            [
                'objectManager' => $this->objectManager,
                'request' => $this->request,
                'response' => $this->response,
                'messageManager' => $this->messageManager,
                'redirect' => $this->redirect,
                'salabilityChecker' => $this->salabilityCheckerMock,
            ]
        );
    }

    /**
     * Execute Download link's sample action with Url link.
     *
     * @return void
     */
    public function testExecuteLinkTypeUrl()
    {
        $linkMock = $this->getMockBuilder(\Magento\Downloadable\Model\Link::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'load', 'getSampleType', 'getSampleUrl'])
            ->getMock();

        $this->request->expects($this->once())->method('getParam')->with('link_id', 0)->willReturn('some_link_id');
        $this->objectManager->expects($this->once())
            ->method('create')
            ->with(\Magento\Downloadable\Model\Link::class)
            ->willReturn($linkMock);
        $linkMock->expects($this->once())->method('load')->with('some_link_id')->willReturnSelf();
        $linkMock->expects($this->once())->method('getId')->willReturn('some_link_id');
        $this->salabilityCheckerMock->expects($this->once())->method('isSalable')->willReturn(true);
        $linkMock->expects($this->once())->method('getSampleType')->willReturn(
            \Magento\Downloadable\Helper\Download::LINK_TYPE_URL
        );
        $linkMock->expects($this->once())->method('getSampleUrl')->willReturn('sample_url');
        $this->objectManager->expects($this->at(1))
            ->method('get')
            ->with(\Magento\Downloadable\Helper\Download::class)
            ->willReturn($this->downloadHelper);
        $this->response->expects($this->once())->method('setHttpResponseCode')->with(200)->willReturnSelf();
        $this->response->expects($this->any())->method('setHeader')->willReturnSelf();
        $this->downloadHelper->expects($this->once())->method('output')->willThrowException(new \Exception());
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('Sorry, there was an error getting requested content. Please contact the store owner.')
            ->willReturnSelf();
        $this->redirect->expects($this->once())->method('getRedirectUrl')->willReturn('redirect_url');
        $this->response->expects($this->once())->method('setRedirect')->with('redirect_url')->willReturnSelf();

        $this->assertEquals($this->response, $this->linkSample->execute());
    }

    /**
     * Execute Download link's sample action with File link.
     *
     * @return void
     */
    public function testExecuteLinkTypeFile()
    {
        $linkMock = $this->getMockBuilder(\Magento\Downloadable\Model\Link::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'load', 'getSampleType', 'getSampleUrl', 'getBaseSamplePath'])
            ->getMock();
        $fileMock = $this->getMockBuilder(\Magento\Downloadable\Helper\File::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFilePath', 'load', 'getSampleType', 'getSampleUrl'])
            ->getMock();

        $this->request->expects($this->once())->method('getParam')->with('link_id', 0)->willReturn('some_link_id');
        $this->objectManager->expects($this->at(0))
            ->method('create')
            ->with(\Magento\Downloadable\Model\Link::class)
            ->willReturn($linkMock);
        $linkMock->expects($this->once())->method('load')->with('some_link_id')->willReturnSelf();
        $linkMock->expects($this->once())->method('getId')->willReturn('some_link_id');
        $this->salabilityCheckerMock->expects($this->once())->method('isSalable')->willReturn(true);
        $linkMock->expects($this->any())->method('getSampleType')->willReturn(
            \Magento\Downloadable\Helper\Download::LINK_TYPE_FILE
        );
        $this->objectManager->expects($this->at(1))
            ->method('get')
            ->with(\Magento\Downloadable\Helper\File::class)
            ->willReturn($fileMock);
        $this->objectManager->expects($this->at(2))
            ->method('get')
            ->with(\Magento\Downloadable\Model\Link::class)
            ->willReturn($linkMock);
        $linkMock->expects($this->once())->method('getBaseSamplePath')->willReturn('downloadable/files/link_samples');
        $this->objectManager->expects($this->at(3))
            ->method('get')
            ->with(\Magento\Downloadable\Helper\Download::class)
            ->willReturn($this->downloadHelper);
        $this->response->expects($this->once())->method('setHttpResponseCode')->with(200)->willReturnSelf();
        $this->response->expects($this->any())->method('setHeader')->willReturnSelf();
        $this->downloadHelper->expects($this->once())->method('output')->willThrowException(new \Exception());
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('Sorry, there was an error getting requested content. Please contact the store owner.')
            ->willReturnSelf();
        $this->redirect->expects($this->once())->method('getRedirectUrl')->willReturn('redirect_url');
        $this->response->expects($this->once())->method('setRedirect')->with('redirect_url')->willReturnSelf();

        $this->assertEquals($this->response, $this->linkSample->execute());
    }
}

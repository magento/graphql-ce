<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Test\Unit\Controller\Adminhtml\Product\Action\Attribute;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save */
    protected $object;

    /** @var \Magento\Catalog\Helper\Product\Edit\Action\Attribute|\PHPUnit_Framework_MockObject_MockObject */
    protected $attributeHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dataObjectHelperMock;

    /** @var \Magento\CatalogInventory\Model\Indexer\Stock\Processor|\PHPUnit_Framework_MockObject_MockObject */
    protected $stockIndexerProcessor;

    /** @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \Magento\Framework\App\Response\Http|\PHPUnit_Framework_MockObject_MockObject */
    protected $response;

    /** @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $objectManager;

    /** @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventManager;

    /** @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $url;

    /** @var \Magento\Framework\App\Response\RedirectInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $redirect;

    /** @var \Magento\Framework\App\ActionFlag|\PHPUnit_Framework_MockObject_MockObject */
    protected $actionFlag;

    /** @var \Magento\Framework\App\ViewInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $view;

    /** @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $messageManager;

    /** @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    /** @var \Magento\Framework\AuthorizationInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $authorization;

    /** @var \Magento\Backend\Model\Auth|\PHPUnit_Framework_MockObject_MockObject */
    protected $auth;

    /** @var \Magento\Backend\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $helper;

    /** @var \Magento\Backend\Model\UrlInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $backendUrl;

    /** @var \Magento\Framework\Data\Form\FormKey\Validator|\PHPUnit_Framework_MockObject_MockObject */
    protected $formKeyValidator;

    /** @var \Magento\Framework\Locale\ResolverInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $localeResolver;

    /** @var \Magento\Catalog\Model\Product|\PHPUnit_Framework_MockObject_MockObject */
    protected $product;

    /** @var \Magento\CatalogInventory\Api\StockRegistryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $stockItemService;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $stockItem;

    /** @var \Magento\CatalogInventory\Api\StockConfigurationInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $stockConfig;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $stockItemRepository;

    /**
     * @var  \Magento\Backend\Model\View\Result\RedirectFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirectFactory;

    protected function setUp()
    {
        $this->attributeHelper = $this->createPartialMock(
            \Magento\Catalog\Helper\Product\Edit\Action\Attribute::class,
            ['getProductIds', 'getSelectedStoreId', 'getStoreWebsiteId']
        );

        $this->dataObjectHelperMock = $this->getMockBuilder(\Magento\Framework\Api\DataObjectHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stockIndexerProcessor = $this->createPartialMock(
            \Magento\CatalogInventory\Model\Indexer\Stock\Processor::class,
            ['reindexList']
        );

        $resultRedirect = $this->getMockBuilder(\Magento\Backend\Model\View\Result\Redirect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultRedirectFactory = $this->getMockBuilder(\Magento\Backend\Model\View\Result\RedirectFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->resultRedirectFactory->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($resultRedirect);

        $this->prepareContext();

        $this->object = (new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this))->getObject(
            \Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save::class,
            [
                'context' => $this->context,
                'attributeHelper' => $this->attributeHelper,
                'stockIndexerProcessor' => $this->stockIndexerProcessor,
                'dataObjectHelper' => $this->dataObjectHelperMock,
            ]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function prepareContext()
    {
        $this->stockItemRepository = $this->getMockBuilder(
            \Magento\CatalogInventory\Api\StockItemRepositoryInterface::class
        )->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()->getMock();
        $this->response = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $this->objectManager = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->eventManager = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $this->url = $this->createMock(\Magento\Framework\UrlInterface::class);
        $this->redirect = $this->createMock(\Magento\Framework\App\Response\RedirectInterface::class);
        $this->actionFlag = $this->createMock(\Magento\Framework\App\ActionFlag::class);
        $this->view = $this->createMock(\Magento\Framework\App\ViewInterface::class);
        $this->messageManager = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $this->session = $this->createMock(\Magento\Backend\Model\Session::class);
        $this->authorization = $this->createMock(\Magento\Framework\AuthorizationInterface::class);
        $this->auth = $this->createMock(\Magento\Backend\Model\Auth::class);
        $this->helper = $this->createMock(\Magento\Backend\Helper\Data::class);
        $this->backendUrl = $this->createMock(\Magento\Backend\Model\UrlInterface::class);
        $this->formKeyValidator = $this->createMock(\Magento\Framework\Data\Form\FormKey\Validator::class);
        $this->localeResolver = $this->createMock(\Magento\Framework\Locale\ResolverInterface::class);

        $this->context = $this->context = $this->createPartialMock(\Magento\Backend\App\Action\Context::class, [
                'getRequest',
                'getResponse',
                'getObjectManager',
                'getEventManager',
                'getUrl',
                'getRedirect',
                'getActionFlag',
                'getView',
                'getMessageManager',
                'getSession',
                'getAuthorization',
                'getAuth',
                'getHelper',
                'getBackendUrl',
                'getFormKeyValidator',
                'getLocaleResolver',
                'getResultRedirectFactory'
            ]);
        $this->context->expects($this->any())->method('getRequest')->willReturn($this->request);
        $this->context->expects($this->any())->method('getResponse')->willReturn($this->response);
        $this->context->expects($this->any())->method('getObjectManager')->willReturn($this->objectManager);
        $this->context->expects($this->any())->method('getEventManager')->willReturn($this->eventManager);
        $this->context->expects($this->any())->method('getUrl')->willReturn($this->url);
        $this->context->expects($this->any())->method('getRedirect')->willReturn($this->redirect);
        $this->context->expects($this->any())->method('getActionFlag')->willReturn($this->actionFlag);
        $this->context->expects($this->any())->method('getView')->willReturn($this->view);
        $this->context->expects($this->any())->method('getMessageManager')->willReturn($this->messageManager);
        $this->context->expects($this->any())->method('getSession')->willReturn($this->session);
        $this->context->expects($this->any())->method('getAuthorization')->willReturn($this->authorization);
        $this->context->expects($this->any())->method('getAuth')->willReturn($this->auth);
        $this->context->expects($this->any())->method('getHelper')->willReturn($this->helper);
        $this->context->expects($this->any())->method('getBackendUrl')->willReturn($this->backendUrl);
        $this->context->expects($this->any())->method('getFormKeyValidator')->willReturn($this->formKeyValidator);
        $this->context->expects($this->any())->method('getLocaleResolver')->willReturn($this->localeResolver);
        $this->context->expects($this->any())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactory);

        $this->product = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            ['isProductsHasSku', '__wakeup']
        );

        $this->stockItemService = $this->getMockBuilder(\Magento\CatalogInventory\Api\StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStockItem', 'saveStockItem'])
            ->getMockForAbstractClass();
        $this->stockItem = $this->getMockBuilder(\Magento\CatalogInventory\Api\Data\StockItemInterface::class)
            ->setMethods(['getId', 'getProductId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->stockConfig = $this->getMockBuilder(\Magento\CatalogInventory\Api\StockConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager->expects($this->any())->method('create')->will($this->returnValueMap([
            [\Magento\Catalog\Model\Product::class, [], $this->product],
            [\Magento\CatalogInventory\Api\StockRegistryInterface::class, [], $this->stockItemService],
            [\Magento\CatalogInventory\Api\StockItemRepositoryInterface::class, [], $this->stockItemRepository],
        ]));

        $this->objectManager->expects($this->any())->method('get')->will($this->returnValueMap([
            [\Magento\CatalogInventory\Api\StockConfigurationInterface::class, $this->stockConfig],
        ]));
    }

    public function testExecuteThatProductIdsAreObtainedFromAttributeHelper()
    {
        $this->attributeHelper->expects($this->any())->method('getProductIds')->will($this->returnValue([5]));
        $this->attributeHelper->expects($this->any())->method('getSelectedStoreId')->will($this->returnValue([1]));
        $this->attributeHelper->expects($this->any())->method('getStoreWebsiteId')->will($this->returnValue(1));
        $this->stockConfig->expects($this->any())->method('getConfigItemOptions')->will($this->returnValue([]));
        $this->dataObjectHelperMock->expects($this->any())
            ->method('populateWithArray')
            ->with($this->stockItem, $this->anything(), \Magento\CatalogInventory\Api\Data\StockItemInterface::class)
            ->willReturnSelf();
        $this->product->expects($this->any())->method('isProductsHasSku')->with([5])->will($this->returnValue(true));
        $this->stockItemService->expects($this->any())->method('getStockItem')->with(5, 1)
            ->will($this->returnValue($this->stockItem));
        $this->stockIndexerProcessor->expects($this->any())->method('reindexList')->with([5]);

        $this->request->expects($this->any())->method('getParam')->will($this->returnValueMap([
            ['inventory', [], [7]],
        ]));

        $this->messageManager->expects($this->never())->method('addErrorMessage');
        $this->messageManager->expects($this->never())->method('addExceptionMessage');

        $this->object->execute();
    }
}

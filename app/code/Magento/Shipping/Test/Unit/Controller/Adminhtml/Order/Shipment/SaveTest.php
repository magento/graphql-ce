<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Shipping\Test\Unit\Controller\Adminhtml\Order\Shipment;

use Magento\Backend\App\Action;
use Magento\Sales\Model\ValidatorResultInterface;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Sales\Model\Order\Shipment\ShipmentValidatorInterface;
use Magento\Sales\Model\Order\Shipment\Validation\QuantityValidator;

/**
 * Class SaveTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shipmentLoader;

    /**
     * @var \Magento\Shipping\Model\Shipping\LabelGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $labelGenerator;

    /**
     * @var ShipmentSender|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shipmentSender;

    /**
     * @var Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /**
     * @var \Magento\Framework\Message\Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * @var \Magento\Framework\App\ActionFlag|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $actionFlag;

    /**
     * @var \Magento\Backend\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Controller\Result\Redirect|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirect;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formKeyValidator;

    /**
     * @var \Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save
     */
    protected $saveAction;

    /**
     * @var ShipmentValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shipmentValidatorMock;

    /**
     * @var ValidatorResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $validationResult;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->shipmentLoader = $this->getMockBuilder(
            \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader::class
        )
            ->disableOriginalConstructor()
            ->setMethods(['setShipmentId', 'setOrderId', 'setShipment', 'setTracking', 'load'])
            ->getMock();
        $this->validationResult = $this->getMockBuilder(ValidatorResultInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->labelGenerator = $this->getMockBuilder(\Magento\Shipping\Model\Shipping\LabelGenerator::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->shipmentSender = $this->getMockBuilder(\Magento\Sales\Model\Order\Email\Sender\ShipmentSender::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->objectManager = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->context = $this->createPartialMock(\Magento\Backend\App\Action\Context::class, [
                'getRequest', 'getResponse', 'getMessageManager', 'getRedirect',
                'getObjectManager', 'getSession', 'getActionFlag', 'getHelper',
                'getResultRedirectFactory', 'getFormKeyValidator'
            ]);
        $this->response = $this->createPartialMock(
            \Magento\Framework\App\ResponseInterface::class,
            ['setRedirect', 'sendResponse']
        );
        $this->request = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()->getMock();
        $this->objectManager = $this->createPartialMock(
            \Magento\Framework\ObjectManager\ObjectManager::class,
            ['create', 'get']
        );
        $this->messageManager = $this->createPartialMock(
            \Magento\Framework\Message\Manager::class,
            ['addSuccessMessage', 'addErrorMessage']
        );
        $this->session = $this->createPartialMock(
            \Magento\Backend\Model\Session::class,
            ['setIsUrlNotice', 'getCommentText']
        );
        $this->actionFlag = $this->createPartialMock(\Magento\Framework\App\ActionFlag::class, ['get']);
        $this->helper = $this->createPartialMock(\Magento\Backend\Helper\Data::class, ['getUrl']);

        $this->resultRedirect = $this->createPartialMock(
            \Magento\Framework\Controller\Result\Redirect::class,
            ['setPath']
        );
        $this->resultRedirect->expects($this->any())
            ->method('setPath')
            ->willReturn($this->resultRedirect);

        $resultRedirectFactory = $this->createPartialMock(
            \Magento\Framework\Controller\Result\RedirectFactory::class,
            ['create']
        );
        $resultRedirectFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultRedirect);

        $this->formKeyValidator = $this->createPartialMock(
            \Magento\Framework\Data\Form\FormKey\Validator::class,
            ['validate']
        );

        $this->context->expects($this->once())
            ->method('getMessageManager')
            ->will($this->returnValue($this->messageManager));
        $this->context->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($this->request));
        $this->context->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($this->response));
        $this->context->expects($this->once())
            ->method('getObjectManager')
            ->will($this->returnValue($this->objectManager));
        $this->context->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($this->session));
        $this->context->expects($this->once())
            ->method('getActionFlag')
            ->will($this->returnValue($this->actionFlag));
        $this->context->expects($this->once())
            ->method('getHelper')
            ->will($this->returnValue($this->helper));
        $this->context->expects($this->once())
            ->method('getResultRedirectFactory')
            ->will($this->returnValue($resultRedirectFactory));
        $this->context->expects($this->once())
            ->method('getFormKeyValidator')
            ->will($this->returnValue($this->formKeyValidator));

        $this->shipmentValidatorMock = $this->getMockBuilder(ShipmentValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->saveAction = $objectManagerHelper->getObject(
            \Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save::class,
            [
                'labelGenerator' => $this->labelGenerator,
                'shipmentSender' => $this->shipmentSender,
                'context' => $this->context,
                'shipmentLoader' => $this->shipmentLoader,
                'request' => $this->request,
                'response' => $this->response,
                'shipmentValidator' => $this->shipmentValidatorMock
            ]
        );
    }

    /**
     * @param bool $formKeyIsValid
     * @param bool $isPost
     * @dataProvider executeDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecute($formKeyIsValid, $isPost)
    {
        $this->formKeyValidator->expects($this->any())
            ->method('validate')
            ->willReturn($formKeyIsValid);

        $this->request->expects($this->any())
            ->method('isPost')
            ->willReturn($isPost);

        if (!$formKeyIsValid || !$isPost) {
            $this->messageManager->expects($this->once())
                ->method('addErrorMessage');

            $this->resultRedirect->expects($this->once())
                ->method('setPath')
                ->with('sales/order/index');

            $this->shipmentLoader->expects($this->never())
                ->method('load');

            $this->assertEquals($this->resultRedirect, $this->saveAction->execute());
        } else {
            $shipmentId = 1000012;
            $orderId = 10003;
            $tracking = [];
            $shipmentData = ['items' => [], 'send_email' => ''];
            $shipment = $this->createPartialMock(
                \Magento\Sales\Model\Order\Shipment::class,
                ['load', 'save', 'register', 'getOrder', 'getOrderId', '__wakeup']
            );
            $order = $this->createPartialMock(\Magento\Sales\Model\Order::class, ['setCustomerNoteNotify', '__wakeup']);

            $this->request->expects($this->any())
                ->method('getParam')
                ->will(
                    $this->returnValueMap(
                        [
                            ['order_id', null, $orderId],
                            ['shipment_id', null, $shipmentId],
                            ['shipment', null, $shipmentData],
                            ['tracking', null, $tracking],
                        ]
                    )
                );

            $this->shipmentLoader->expects($this->any())
                ->method('setShipmentId')
                ->with($shipmentId);
            $this->shipmentLoader->expects($this->any())
                ->method('setOrderId')
                ->with($orderId);
            $this->shipmentLoader->expects($this->any())
                ->method('setShipment')
                ->with($shipmentData);
            $this->shipmentLoader->expects($this->any())
                ->method('setTracking')
                ->with($tracking);
            $this->shipmentLoader->expects($this->once())
                ->method('load')
                ->will($this->returnValue($shipment));
            $shipment->expects($this->once())
                ->method('register')
                ->will($this->returnSelf());
            $shipment->expects($this->any())
                ->method('getOrder')
                ->will($this->returnValue($order));
            $order->expects($this->once())
                ->method('setCustomerNoteNotify')
                ->with(false);
            $this->labelGenerator->expects($this->any())
                ->method('create')
                ->with($shipment, $this->request)
                ->will($this->returnValue(true));
            $saveTransaction = $this->getMockBuilder(\Magento\Framework\DB\Transaction::class)
                ->disableOriginalConstructor()
                ->setMethods([])
                ->getMock();
            $saveTransaction->expects($this->at(0))
                ->method('addObject')
                ->with($shipment)
                ->will($this->returnSelf());
            $saveTransaction->expects($this->at(1))
                ->method('addObject')
                ->with($order)
                ->will($this->returnSelf());
            $saveTransaction->expects($this->at(2))
                ->method('save');

            $this->session->expects($this->once())
                ->method('getCommentText')
                ->with(true);

            $this->objectManager->expects($this->once())
                ->method('create')
                ->with(\Magento\Framework\DB\Transaction::class)
                ->will($this->returnValue($saveTransaction));
            $this->objectManager->expects($this->once())
                ->method('get')
                ->with(\Magento\Backend\Model\Session::class)
                ->will($this->returnValue($this->session));
            $arguments = ['order_id' => $orderId];
            $shipment->expects($this->once())
                ->method('getOrderId')
                ->will($this->returnValue($orderId));
            $this->prepareRedirect($arguments);

            $this->shipmentValidatorMock->expects($this->once())
                ->method('validate')
                ->with($shipment, [QuantityValidator::class])
                ->willReturn($this->validationResult);

            $this->validationResult->expects($this->once())
                ->method('hasMessages')
                ->willReturn(false);

            $this->saveAction->execute();
            $this->assertEquals($this->response, $this->saveAction->getResponse());
        }
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [false, false],
            [true, false],
            [false, true],
            [true, true]
        ];
    }

    /**
     * @param array $arguments
     */
    protected function prepareRedirect(array $arguments = [])
    {
        $this->actionFlag->expects($this->any())
            ->method('get')
            ->with('', 'check_url_settings')
            ->will($this->returnValue(true));
        $this->session->expects($this->any())
            ->method('setIsUrlNotice')
            ->with(true);
        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('sales/order/view', $arguments);
    }
}

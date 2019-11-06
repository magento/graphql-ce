<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Customer\Test\Unit\Helper\Session;

/**
 * Current customer test.
 */
class CurrentCustomerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSessionMock;

    /**
     * @var \Magento\Framework\View\LayoutInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $layoutMock;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerInterfaceFactoryMock;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerDataMock;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerRepositoryMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\Module\Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $moduleManagerMock;

    /**
     * @var \Magento\Framework\App\ViewInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $viewMock;

    /**
     * @var int
     */
    protected $customerId = 100;

    /**
     * @var int
     */
    protected $customerGroupId = 500;

    /**
     * Test setup
     */
    protected function setUp()
    {
        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->layoutMock = $this->createMock(\Magento\Framework\View\Layout::class);
        $this->customerInterfaceFactoryMock = $this->createPartialMock(
            \Magento\Customer\Api\Data\CustomerInterfaceFactory::class,
            ['create', 'setGroupId']
        );
        $this->customerDataMock = $this->createMock(\Magento\Customer\Api\Data\CustomerInterface::class);
        $this->customerRepositoryMock = $this->createMock(\Magento\Customer\Api\CustomerRepositoryInterface::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->moduleManagerMock = $this->createMock(\Magento\Framework\Module\Manager::class);
        $this->viewMock = $this->createMock(\Magento\Framework\App\View::class);

        $this->currentCustomer = new \Magento\Customer\Helper\Session\CurrentCustomer(
            $this->customerSessionMock,
            $this->layoutMock,
            $this->customerInterfaceFactoryMock,
            $this->customerRepositoryMock,
            $this->requestMock,
            $this->moduleManagerMock,
            $this->viewMock
        );
    }

    /**
     * test getCustomer method, method returns depersonalized customer Data
     */
    public function testGetCustomerDepersonalizeCustomerData()
    {
        $this->requestMock->expects($this->once())->method('isAjax')->will($this->returnValue(false));
        $this->layoutMock->expects($this->once())->method('isCacheable')->will($this->returnValue(true));
        $this->viewMock->expects($this->once())->method('isLayoutLoaded')->will($this->returnValue(true));
        $this->moduleManagerMock->expects($this->once())
            ->method('isEnabled')
            ->with($this->equalTo('Magento_PageCache'))
            ->will($this->returnValue(true));
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerGroupId')
            ->will($this->returnValue($this->customerGroupId));
        $this->customerInterfaceFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->customerDataMock));
        $this->customerDataMock->expects($this->once())
            ->method('setGroupId')
            ->with($this->equalTo($this->customerGroupId))
            ->will($this->returnSelf());
        $this->assertEquals($this->customerDataMock, $this->currentCustomer->getCustomer());
    }

    /**
     * test get customer method, method returns customer from service
     */
    public function testGetCustomerLoadCustomerFromService()
    {
        $this->moduleManagerMock->expects($this->once())
            ->method('isEnabled')
            ->with($this->equalTo('Magento_PageCache'))
            ->will($this->returnValue(false));
        $this->customerSessionMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($this->customerId));
        $this->customerRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($this->equalTo($this->customerId))
            ->will($this->returnValue($this->customerDataMock));
        $this->assertEquals($this->customerDataMock, $this->currentCustomer->getCustomer());
    }
}

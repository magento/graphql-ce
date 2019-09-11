<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Customer\Test\Unit\Model;

use Magento\Customer\Model\CustomerExtractor;

/**
 * Unit test CustomerExtractorTest
 */
class CustomerExtractorTest extends \PHPUnit\Framework\TestCase
{
    /** @var CustomerExtractor */
    protected $customerExtractor;

    /** @var \Magento\Customer\Model\Metadata\FormFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var \Magento\Customer\Api\Data\CustomerInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $customerFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $storeManager;

    /** @var \Magento\Customer\Api\GroupManagementInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $customerGroupManagement;

    /** @var \Magento\Framework\Api\DataObjectHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $dataObjectHelper;

    /** @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var \Magento\Customer\Model\Metadata\Form|\PHPUnit_Framework_MockObject_MockObject */
    protected $customerForm;

    /** @var \Magento\Customer\Api\Data\CustomerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $customerData;

    /** @var \Magento\Store\Api\Data\StoreInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $store;

    /** @var \Magento\Customer\Api\Data\GroupInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $customerGroup;

    protected function setUp()
    {
        $this->formFactory = $this->getMockForAbstractClass(
            \Magento\Customer\Model\Metadata\FormFactory::class,
            [],
            '',
            false,
            false,
            true,
            ['create']
        );
        $this->customerFactory = $this->getMockForAbstractClass(
            \Magento\Customer\Api\Data\CustomerInterfaceFactory::class,
            [],
            '',
            false,
            false,
            true,
            ['create']
        );
        $this->storeManager = $this->getMockForAbstractClass(
            \Magento\Store\Model\StoreManagerInterface::class,
            [],
            '',
            false
        );
        $this->customerGroupManagement = $this->getMockForAbstractClass(
            \Magento\Customer\Api\GroupManagementInterface::class,
            [],
            '',
            false
        );
        $this->dataObjectHelper = $this->createMock(\Magento\Framework\Api\DataObjectHelper::class);
        $this->request = $this->getMockForAbstractClass(\Magento\Framework\App\RequestInterface::class, [], '', false);
        $this->customerForm = $this->createMock(\Magento\Customer\Model\Metadata\Form::class);
        $this->customerData = $this->getMockForAbstractClass(
            \Magento\Customer\Api\Data\CustomerInterface::class,
            [],
            '',
            false
        );
        $this->store = $this->getMockForAbstractClass(
            \Magento\Store\Api\Data\StoreInterface::class,
            [],
            '',
            false
        );
        $this->customerGroup = $this->getMockForAbstractClass(
            \Magento\Customer\Api\Data\GroupInterface::class,
            [],
            '',
            false
        );
        $this->customerExtractor = new CustomerExtractor(
            $this->formFactory,
            $this->customerFactory,
            $this->storeManager,
            $this->customerGroupManagement,
            $this->dataObjectHelper
        );
    }

    public function testExtract()
    {
        $customerData = [
            'firstname' => 'firstname',
            'lastname' => 'firstname',
            'email' => 'email.example.com',
        ];

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with('customer', 'form-code')
            ->willReturn($this->customerForm);
        $this->customerForm->expects($this->once())
            ->method('extractData')
            ->with($this->request)
            ->willReturn($customerData);
        $this->customerForm->expects($this->once())
            ->method('compactData')
            ->with($customerData)
            ->willReturn($customerData);
        $this->customerForm->expects($this->once())
            ->method('getAllowedAttributes')
            ->willReturn(['group_id' => 'attribute object']);
        $this->customerFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->customerData);
        $this->dataObjectHelper->expects($this->once())
            ->method('populateWithArray')
            ->with($this->customerData, $customerData, \Magento\Customer\Api\Data\CustomerInterface::class)
            ->willReturn($this->customerData);
        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($this->store);
        $this->store->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->store->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn(1);
        $this->customerData->expects($this->once())
            ->method('setWebsiteId')
            ->with(1);
        $this->customerData->expects($this->once())
            ->method('setStoreId')
            ->with(1);

        $this->assertSame($this->customerData, $this->customerExtractor->extract('form-code', $this->request));
    }
}

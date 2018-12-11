<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Quote\Test\Unit\Model;

use \Magento\Quote\Model\ShippingAddressManagement;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShippingAddressManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingAddressManagement
     */
    protected $service;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteAddressMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $validatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $totalsCollectorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $addressRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $amountErrorMessageMock;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->quoteRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->scopeConfigMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        $this->quoteAddressMock = $this->createPartialMock(\Magento\Quote\Model\Quote\Address::class, [
                'setSameAsBilling',
                'setCollectShippingRates',
                '__wakeup',
                'collectTotals',
                'save',
                'getId',
                'getCustomerAddressId',
                'getSaveInAddressBook',
                'getSameAsBilling',
                'importCustomerAddressData',
                'setSaveInAddressBook',
            ]);
        $this->validatorMock = $this->createMock(\Magento\Quote\Model\QuoteAddressValidator::class);
        $this->totalsCollectorMock = $this->createMock(\Magento\Quote\Model\Quote\TotalsCollector::class);
        $this->addressRepository = $this->createMock(\Magento\Customer\Api\AddressRepositoryInterface::class);

        $this->amountErrorMessageMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote\Validator\MinimumOrderAmount\ValidationMessage::class,
            ['getMessage']
        );

        $this->service = $this->objectManager->getObject(
            \Magento\Quote\Model\ShippingAddressManagement::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'addressValidator' => $this->validatorMock,
                'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
                'scopeConfig' => $this->scopeConfigMock,
                'totalsCollector' => $this->totalsCollectorMock,
                'addressRepository' => $this->addressRepository
            ]
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage error345
     */
    public function testSetAddressValidationFailed()
    {
        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->quoteRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with('cart654')
            ->will($this->returnValue($quoteMock));

        $this->validatorMock->expects($this->once())->method('validate')
            ->will($this->throwException(new \Magento\Framework\Exception\NoSuchEntityException(__('error345'))));

        $this->service->assign('cart654', $this->quoteAddressMock);
    }

    public function testSetAddress()
    {
        $addressId = 1;
        $customerAddressId = 150;

        $quoteMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            ['getIsMultiShipping', 'isVirtual', 'validateMinimumAmount', 'setShippingAddress', 'getShippingAddress']
        );
        $this->quoteRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with('cart867')
            ->willReturn($quoteMock);
        $quoteMock->expects($this->once())->method('isVirtual')->will($this->returnValue(false));
        $quoteMock->expects($this->once())
            ->method('setShippingAddress')
            ->with($this->quoteAddressMock)
            ->willReturnSelf();

        $this->quoteAddressMock->expects($this->once())->method('getSaveInAddressBook')->willReturn(1);
        $this->quoteAddressMock->expects($this->once())->method('getSameAsBilling')->willReturn(1);
        $this->quoteAddressMock->expects($this->once())->method('getCustomerAddressId')->willReturn($customerAddressId);

        $customerAddressMock = $this->createMock(\Magento\Customer\Api\Data\AddressInterface::class);

        $this->addressRepository->expects($this->once())
            ->method('getById')
            ->with($customerAddressId)
            ->willReturn($customerAddressMock);

        $this->validatorMock->expects($this->once())->method('validate')
            ->with($this->quoteAddressMock)
            ->willReturn(true);

        $quoteMock->expects($this->exactly(3))->method('getShippingAddress')->willReturn($this->quoteAddressMock);
        $this->quoteAddressMock->expects($this->once())
            ->method('importCustomerAddressData')
            ->with($customerAddressMock)
            ->willReturnSelf();

        $this->quoteAddressMock->expects($this->once())->method('setSameAsBilling')->with(1)->willReturnSelf();
        $this->quoteAddressMock->expects($this->once())->method('setSaveInAddressBook')->with(1)->willReturnSelf();
        $this->quoteAddressMock->expects($this->once())
            ->method('setCollectShippingRates')
            ->with(true)
            ->willReturnSelf();

        $this->quoteAddressMock->expects($this->once())->method('save')->willReturnSelf();
        $this->quoteAddressMock->expects($this->once())->method('getId')->will($this->returnValue($addressId));

        $this->assertEquals($addressId, $this->service->assign('cart867', $this->quoteAddressMock));
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage The Cart includes virtual product(s) only, so a shipping address is not used.
     */
    public function testSetAddressForVirtualProduct()
    {
        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->quoteRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with('cart867')
            ->will($this->returnValue($quoteMock));
        $quoteMock->expects($this->once())->method('isVirtual')->will($this->returnValue(true));
        $quoteMock->expects($this->never())->method('setShippingAddress');

        $this->quoteAddressMock->expects($this->never())->method('getCustomerAddressId');
        $this->quoteAddressMock->expects($this->never())->method('setSaveInAddressBook');

        $quoteMock->expects($this->never())->method('save');

        $this->service->assign('cart867', $this->quoteAddressMock);
    }

    /**
     * @expectedException \Magento\Framework\Exception\InputException
     * @expectedExceptionMessage The address failed to save. Verify the address and try again.
     */
    public function testSetAddressWithInabilityToSaveQuote()
    {
        $this->quoteAddressMock->expects($this->once())->method('save')->willThrowException(
            new \Exception('The address failed to save. Verify the address and try again.')
        );

        $customerAddressId = 150;

        $quoteMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            ['getIsMultiShipping', 'isVirtual', 'validateMinimumAmount', 'setShippingAddress', 'getShippingAddress']
        );
        $this->quoteRepositoryMock->expects($this->once())
            ->method('getActive')
            ->with('cart867')
            ->willReturn($quoteMock);
        $quoteMock->expects($this->once())->method('isVirtual')->will($this->returnValue(false));
        $quoteMock->expects($this->once())
            ->method('setShippingAddress')
            ->with($this->quoteAddressMock)
            ->willReturnSelf();

        $customerAddressMock = $this->createMock(\Magento\Customer\Api\Data\AddressInterface::class);

        $this->addressRepository->expects($this->once())
            ->method('getById')
            ->with($customerAddressId)
            ->willReturn($customerAddressMock);

        $this->validatorMock->expects($this->once())->method('validate')
            ->with($this->quoteAddressMock)
            ->willReturn(true);

        $this->quoteAddressMock->expects($this->once())->method('getSaveInAddressBook')->willReturn(1);
        $this->quoteAddressMock->expects($this->once())->method('getSameAsBilling')->willReturn(1);
        $this->quoteAddressMock->expects($this->once())->method('getCustomerAddressId')->willReturn($customerAddressId);

        $quoteMock->expects($this->exactly(2))->method('getShippingAddress')->willReturn($this->quoteAddressMock);
        $this->quoteAddressMock->expects($this->once())
            ->method('importCustomerAddressData')
            ->with($customerAddressMock)
            ->willReturnSelf();

        $this->quoteAddressMock->expects($this->once())->method('setSameAsBilling')->with(1)->willReturnSelf();
        $this->quoteAddressMock->expects($this->once())->method('setSaveInAddressBook')->with(1)->willReturnSelf();
        $this->quoteAddressMock->expects($this->once())
            ->method('setCollectShippingRates')
            ->with(true)
            ->willReturnSelf();

        $this->service->assign('cart867', $this->quoteAddressMock);
    }

    public function testGetAddress()
    {
        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->quoteRepositoryMock->expects($this->once())->method('getActive')->with('cartId')->will(
            $this->returnValue($quoteMock)
        );

        $addressMock = $this->createMock(\Magento\Quote\Model\Quote\Address::class);
        $quoteMock->expects($this->any())->method('getShippingAddress')->will($this->returnValue($addressMock));
        $quoteMock->expects($this->any())->method('isVirtual')->will($this->returnValue(false));
        $this->assertEquals($addressMock, $this->service->get('cartId'));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The Cart includes virtual product(s) only, so a shipping address is not used.
     */
    public function testGetAddressOfQuoteWithVirtualProducts()
    {
        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->quoteRepositoryMock->expects($this->once())->method('getActive')->with('cartId')->will(
            $this->returnValue($quoteMock)
        );

        $quoteMock->expects($this->any())->method('isVirtual')->will($this->returnValue(true));
        $quoteMock->expects($this->never())->method('getShippingAddress');

        $this->service->get('cartId');
    }
}

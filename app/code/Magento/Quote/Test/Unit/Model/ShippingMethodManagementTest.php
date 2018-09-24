<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Quote\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Model\ResourceModel\Quote\Address as QuoteAddressResource;
use Magento\Quote\Model\ShippingMethodManagement;
use Magento\Store\Model\Store;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShippingMethodManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingMethodManagement
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingMethodMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $methodDataFactoryMock;

    /**
     * @var ShippingMethodConverter|MockObject
     */
    protected $converter;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var QuoteRepository|MockObject
     */
    private $quoteRepository;

    /**
     * @var Quote|MockObject
     */
    private $quote;

    /**
     * @var Address|MockObject
     */
    private $shippingAddress;

    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor|MockObject
     */
    private $dataProcessor;

    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory|MockObject
     */
    private $addressFactory;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface|MockObject
     */
    private $addressRepository;

    /**
     * @var TotalsCollector|MockObject
     */
    private $totalsCollector;

    /**
     * @var Store|MockObject
     */
    private $storeMock;

    /**
     * @var QuoteAddressResource|MockObject
     */
    private $quoteAddressResource;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->quoteRepository = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->addressRepository = $this->createMock(\Magento\Customer\Api\AddressRepositoryInterface::class);

        $className = \Magento\Quote\Api\Data\ShippingMethodInterfaceFactory::class;
        $this->methodDataFactoryMock = $this->createPartialMock($className, ['create']);

        $className = \Magento\Customer\Api\Data\AddressInterfaceFactory::class;
        $this->addressFactory = $this->createPartialMock($className, ['create']);

        $className = \Magento\Framework\Reflection\DataObjectProcessor::class;
        $this->dataProcessor = $this->createMock($className);

        $this->quoteAddressResource = $this->createMock(QuoteAddressResource::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getShippingAddress',
                'isVirtual',
                'getItemsCount',
                'getQuoteCurrencyCode',
                'getBillingAddress',
                'collectTotals',
                'save',
                '__wakeup',
            ])
            ->getMock();

        $this->shippingAddress = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCountryId',
                'getShippingMethod',
                'getShippingDescription',
                'getShippingAmount',
                'getBaseShippingAmount',
                'getGroupedAllShippingRates',
                'collectShippingRates',
                'requestShippingRates',
                'setShippingMethod',
                'getShippingRateByCode',
                'addData',
                'setCollectShippingRates',
                '__wakeup',
            ])
            ->getMock();

        $this->converter = $this->getMockBuilder(ShippingMethodConverter::class)
            ->disableOriginalConstructor()
            ->setMethods(['modelToDataObject'])
            ->getMock();

        $this->totalsCollector = $this->getMockBuilder(TotalsCollector::class)
            ->disableOriginalConstructor()
            ->setMethods(['collectAddressTotals'])
            ->getMock();

        $this->model = $this->objectManager->getObject(
            ShippingMethodManagement::class,
            [
                'quoteRepository' => $this->quoteRepository,
                'methodDataFactory' => $this->methodDataFactoryMock,
                'converter' => $this->converter,
                'totalsCollector' => $this->totalsCollector,
                'addressRepository' => $this->addressRepository,
                'quoteAddressResource' => $this->quoteAddressResource,
            ]
        );

        $this->objectManager->setBackwardCompatibleProperty(
            $this->model,
            'addressFactory',
            $this->addressFactory
        );

        $this->objectManager->setBackwardCompatibleProperty(
            $this->model,
            'dataProcessor',
            $this->dataProcessor
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\StateException
     * @expectedExceptionMessage The shipping address is missing. Set the address and try again.
     */
    public function testGetMethodWhenShippingAddressIsNotSet()
    {
        $cartId = 666;
        $this->quoteRepository->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quote));
        $this->quote->expects($this->once())
            ->method('getShippingAddress')->will($this->returnValue($this->shippingAddress));
        $this->shippingAddress->expects($this->once())->method('getCountryId')->will($this->returnValue(null));

        $this->assertNull($this->model->get($cartId));
    }

    public function testGetMethod()
    {
        $cartId = 666;
        $countryId = 1;
        $currencyCode = 'US_dollar';
        $this->quoteRepository->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quote));
        $this->quote->expects($this->once())
            ->method('getShippingAddress')->will($this->returnValue($this->shippingAddress));
        $this->quote->expects($this->once())
            ->method('getQuoteCurrencyCode')->willReturn($currencyCode);
        $this->shippingAddress->expects($this->any())
            ->method('getCountryId')->will($this->returnValue($countryId));
        $this->shippingAddress->expects($this->any())
            ->method('getShippingMethod')->will($this->returnValue('one_two'));

        $this->shippingAddress->expects($this->once())->method('collectShippingRates')->willReturnSelf();
        $shippingRateMock = $this->createMock(\Magento\Quote\Model\Quote\Address\Rate::class);

        $this->shippingAddress->expects($this->once())
            ->method('getShippingRateByCode')
            ->with('one_two')
            ->willReturn($shippingRateMock);

        $this->shippingMethodMock = $this->createMock(\Magento\Quote\Api\Data\ShippingMethodInterface::class);
        $this->converter->expects($this->once())
            ->method('modelToDataObject')
            ->with($shippingRateMock, $currencyCode)
            ->willReturn($this->shippingMethodMock);
        $this->model->get($cartId);
    }

    public function testGetMethodIfMethodIsNotSet()
    {
        $cartId = 666;
        $countryId = 1;

        $this->quoteRepository->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quote));
        $this->quote->expects($this->once())
            ->method('getShippingAddress')->will($this->returnValue($this->shippingAddress));
        $this->shippingAddress->expects($this->any())
            ->method('getCountryId')->will($this->returnValue($countryId));
        $this->shippingAddress->expects($this->any())
            ->method('getShippingMethod')->will($this->returnValue(null));

        $this->assertNull($this->model->get($cartId));
    }

    public function testGetListForVirtualCart()
    {
        $cartId = 834;
        $this->quoteRepository->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quote));
        $this->quote->expects($this->once())
            ->method('isVirtual')->will($this->returnValue(true));

        $this->assertEquals([], $this->model->getList($cartId));
    }

    public function testGetListForEmptyCart()
    {
        $cartId = 834;
        $this->quoteRepository->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quote));
        $this->quote->expects($this->once())
            ->method('isVirtual')->will($this->returnValue(false));
        $this->quote->expects($this->once())
            ->method('getItemsCount')->will($this->returnValue(0));

        $this->assertEquals([], $this->model->getList($cartId));
    }

    /**
     * @expectedException \Magento\Framework\Exception\StateException
     * @expectedExceptionMessage The shipping address is missing. Set the address and try again.
     */
    public function testGetListWhenShippingAddressIsNotSet()
    {
        $cartId = 834;
        $this->quoteRepository->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quote));
        $this->quote->expects($this->once())
            ->method('isVirtual')->will($this->returnValue(false));
        $this->quote->expects($this->once())
            ->method('getItemsCount')->will($this->returnValue(3));
        $this->quote->expects($this->once())
            ->method('getShippingAddress')->will($this->returnValue($this->shippingAddress));
        $this->shippingAddress->expects($this->once())->method('getCountryId')->will($this->returnValue(null));

        $this->model->getList($cartId);
    }

    public function testGetList()
    {
        $cartId = 834;
        $this->quoteRepository->expects($this->once())
            ->method('getActive')->with($cartId)->will($this->returnValue($this->quote));
        $this->quote->expects($this->once())
            ->method('isVirtual')->will($this->returnValue(false));
        $this->quote->expects($this->once())
            ->method('getItemsCount')->will($this->returnValue(3));
        $this->quote->expects($this->once())
            ->method('getShippingAddress')->will($this->returnValue($this->shippingAddress));
        $this->shippingAddress->expects($this->once())->method('getCountryId')->will($this->returnValue(345));
        $this->shippingAddress->expects($this->once())->method('collectShippingRates');
        $shippingRateMock = $this->createMock(\Magento\Quote\Model\Quote\Address\Rate::class);
        $this->shippingAddress->expects($this->once())
            ->method('getGroupedAllShippingRates')
            ->will($this->returnValue([[$shippingRateMock]]));

        $currencyCode = 'EUR';
        $this->quote->expects($this->once())
            ->method('getQuoteCurrencyCode')
            ->will($this->returnValue($currencyCode));

        $this->converter->expects($this->once())
            ->method('modelToDataObject')
            ->with($shippingRateMock, $currencyCode)
            ->will($this->returnValue('RateValue'));
        $this->assertEquals(['RateValue'], $this->model->getList($cartId));
    }

    /**
     * @expectedException \Magento\Framework\Exception\InputException
     * @expectedExceptionMessage The shipping method can't be set for an empty cart. Add an item to cart and try again.
     */
    public function testSetMethodWithInputException()
    {
        $cartId = 12;
        $carrierCode = 34;
        $methodCode = 56;
        $this->quoteRepository->expects($this->exactly(2))
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getItemsCount')->will($this->returnValue(0));
        $this->quote->expects($this->never())->method('isVirtual');

        $this->model->set($cartId, $carrierCode, $methodCode);
    }

    /**
     * @expectedException \Magento\Framework\Exception\NoSuchEntityException
     * @expectedExceptionMessage The Cart includes virtual product(s) only, so a shipping address is not used.
     */
    public function testSetMethodWithVirtualProduct()
    {
        $cartId = 12;
        $carrierCode = 34;
        $methodCode = 56;

        $this->quoteRepository->expects($this->exactly(2))
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getItemsCount')->will($this->returnValue(1));
        $this->quote->expects($this->once())->method('isVirtual')->will($this->returnValue(true));

        $this->model->set($cartId, $carrierCode, $methodCode);
    }

    /**
     * @expectedException \Magento\Framework\Exception\StateException
     * @expectedExceptionMessage The shipping address is missing. Set the address and try again.
     */
    public function testSetMethodWithoutShippingAddress()
    {
        $cartId = 12;
        $carrierCode = 34;
        $methodCode = 56;
        $this->quoteRepository->expects($this->exactly(2))
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getItemsCount')->will($this->returnValue(1));
        $this->quote->expects($this->once())->method('isVirtual')->will($this->returnValue(false));
        $this->quote->expects($this->once())
            ->method('getShippingAddress')->will($this->returnValue($this->shippingAddress));
        $this->shippingAddress->expects($this->once())->method('getCountryId')->will($this->returnValue(null));
        $this->quoteAddressResource->expects($this->once())->method('delete')->with($this->shippingAddress);

        $this->model->set($cartId, $carrierCode, $methodCode);
    }

    /**
     * @expectedException \Magento\Framework\Exception\CouldNotSaveException
     * @expectedExceptionMessage The shipping method can't be set. Custom Error
     */
    public function testSetMethodWithCouldNotSaveException()
    {
        $cartId = 12;
        $carrierCode = 34;
        $methodCode = 56;
        $countryId = 1;

        $this->quoteRepository->expects($this->exactly(2))
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getItemsCount')->will($this->returnValue(1));
        $this->quote->expects($this->once())->method('isVirtual')->will($this->returnValue(false));
        $this->quote->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddress);
        $this->shippingAddress->expects($this->once())
            ->method('getCountryId')
            ->willReturn($countryId);
        $this->shippingAddress->expects($this->once())
            ->method('setShippingMethod')
            ->with($carrierCode . '_' . $methodCode);
        $exception = new \Exception('Custom Error');
        $this->quote->expects($this->once())->method('collectTotals')->willReturnSelf();
        $this->quoteRepository->expects($this->once())
            ->method('save')
            ->with($this->quote)
            ->willThrowException($exception);

        $this->model->set($cartId, $carrierCode, $methodCode);
    }

    /**
     * @expectedException \Magento\Framework\Exception\StateException
     * @expectedExceptionMessage The shipping address is missing. Set the address and try again.
     */
    public function testSetMethodWithoutAddress()
    {
        $cartId = 12;
        $carrierCode = 34;
        $methodCode = 56;
        $this->quoteRepository->expects($this->exactly(2))
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getItemsCount')->will($this->returnValue(1));
        $this->quote->expects($this->once())->method('isVirtual')->will($this->returnValue(false));
        $this->quote->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddress);
        $this->shippingAddress->expects($this->once())->method('getCountryId');
        $this->quoteAddressResource->expects($this->once())->method('delete')->with($this->shippingAddress);

        $this->model->set($cartId, $carrierCode, $methodCode);
    }

    public function testSetMethod()
    {
        $cartId = 12;
        $carrierCode = 34;
        $methodCode = 56;
        $countryId = 1;
        $this->quoteRepository->expects($this->exactly(2))
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getItemsCount')->will($this->returnValue(1));
        $this->quote->expects($this->once())->method('isVirtual')->will($this->returnValue(false));
        $this->quote->expects($this->once())
            ->method('getShippingAddress')->will($this->returnValue($this->shippingAddress));
        $this->shippingAddress->expects($this->once())
            ->method('getCountryId')->will($this->returnValue($countryId));
        $this->shippingAddress->expects($this->once())
            ->method('setShippingMethod')->with($carrierCode . '_' . $methodCode);
        $this->quote->expects($this->once())->method('collectTotals')->will($this->returnSelf());
        $this->quoteRepository->expects($this->once())->method('save')->with($this->quote);

        $this->assertTrue($this->model->set($cartId, $carrierCode, $methodCode));
    }

    /**
     * @covers \Magento\Quote\Model\ShippingMethodManagement::estimateByExtendedAddress
     */
    public function testEstimateByExtendedAddress()
    {
        $cartId = 1;

        $addressData = [
            'region' => 'California',
            'region_id' => 23,
            'country_id' => 1,
            'postcode' => 90200,
        ];
        $currencyCode = 'UAH';

        /**
         * @var \Magento\Quote\Api\Data\AddressInterface|MockObject $address
         */
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($address));

        $this->quoteRepository->expects(static::once())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quote);

        $this->quote->expects(static::once())
            ->method('isVirtual')
            ->willReturn(false);
        $this->quote->expects(static::once())
            ->method('getItemsCount')
            ->willReturn(1);

        $this->quote->expects(static::once())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddress);

        $this->dataProcessor->expects(static::any())
            ->method('buildOutputDataArray')
            ->willReturn($addressData);

        $this->shippingAddress->expects(static::once())
            ->method('setCollectShippingRates')
            ->with(true)
            ->willReturnSelf();

        $this->totalsCollector->expects(static::once())
            ->method('collectAddressTotals')
            ->with($this->quote, $this->shippingAddress)
            ->willReturnSelf();

        $rate = $this->getMockBuilder(Rate::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $methodObject = $this->getMockForAbstractClass(ShippingMethodInterface::class);
        $expectedRates = [$methodObject];

        $this->shippingAddress->expects(static::once())
            ->method('getGroupedAllShippingRates')
            ->willReturn([[$rate]]);

        $this->quote->expects(static::once())
            ->method('getQuoteCurrencyCode')
            ->willReturn($currencyCode);

        $this->converter->expects(static::once())
            ->method('modelToDataObject')
            ->with($rate, $currencyCode)
            ->willReturn($methodObject);

        $carriersRates = $this->model->estimateByExtendedAddress($cartId, $address);
        static::assertEquals($expectedRates, $carriersRates);
    }

    /**
     * @covers \Magento\Quote\Model\ShippingMethodManagement::estimateByAddressId
     */
    public function testEstimateByAddressId()
    {
        $cartId = 1;

        $addressData = [
            'region' => 'California',
            'region_id' => 23,
            'country_id' => 1,
            'postcode' => 90200,
        ];
        $currencyCode = 'UAH';

        /**
         * @var \Magento\Customer\Api\Data\AddressInterface|MockObject $address
         */
        $address = $this->getMockBuilder(\Magento\Customer\Api\Data\AddressInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressRepository->expects($this->any())
            ->method('getById')
            ->will($this->returnValue($address));

        $this->addressFactory->expects($this->any())
            ->method('create')
            ->will($this->returnValue($address));

        $this->quoteRepository->expects(static::once())
            ->method('getActive')
            ->with($cartId)
            ->willReturn($this->quote);

        $this->quote->expects(static::once())
            ->method('isVirtual')
            ->willReturn(false);
        $this->quote->expects(static::once())
            ->method('getItemsCount')
            ->willReturn(1);

        $this->quote->expects(static::once())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddress);

        $this->dataProcessor->expects(static::any())
            ->method('buildOutputDataArray')
            ->willReturn($addressData);

        $this->shippingAddress->expects(static::once())
            ->method('setCollectShippingRates')
            ->with(true)
            ->willReturnSelf();

        $this->totalsCollector->expects(static::once())
            ->method('collectAddressTotals')
            ->with($this->quote, $this->shippingAddress)
            ->willReturnSelf();

        $rate = $this->getMockBuilder(Rate::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $methodObject = $this->getMockForAbstractClass(ShippingMethodInterface::class);
        $expectedRates = [$methodObject];

        $this->shippingAddress->expects(static::once())
            ->method('getGroupedAllShippingRates')
            ->willReturn([[$rate]]);

        $this->quote->expects(static::once())
            ->method('getQuoteCurrencyCode')
            ->willReturn($currencyCode);

        $this->converter->expects(static::once())
            ->method('modelToDataObject')
            ->with($rate, $currencyCode)
            ->willReturn($methodObject);

        $carriersRates = $this->model->estimateByAddressId($cartId, $address);
        static::assertEquals($expectedRates, $carriersRates);
    }
}

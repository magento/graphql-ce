<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Quote\Model;

use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\EstimateAddressInterface;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Magento\Quote\Model\ResourceModel\Quote\Address as QuoteAddressResource;

/**
 * Shipping method read service
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShippingMethodManagement implements
    \Magento\Quote\Api\ShippingMethodManagementInterface,
    \Magento\Quote\Model\ShippingMethodManagementInterface,
    ShipmentEstimationInterface
{
    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Shipping method converter
     *
     * @var \Magento\Quote\Model\Cart\ShippingMethodConverter
     */
    protected $converter;

    /**
     * Customer Address repository
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var Quote\TotalsCollector
     */
    protected $totalsCollector;

    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor
     */
    private $dataProcessor;

    /**
     * @var AddressInterfaceFactory $addressFactory
     */
    private $addressFactory;

    /**
     * @var QuoteAddressResource
     */
    private $quoteAddressResource;

    /**
     * Constructor
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param Cart\ShippingMethodConverter $converter
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param Quote\TotalsCollector $totalsCollector
     * @param AddressInterfaceFactory|null $addressFactory
     * @param QuoteAddressResource|null $quoteAddressResource
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        Cart\ShippingMethodConverter $converter,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        AddressInterfaceFactory $addressFactory = null,
        QuoteAddressResource $quoteAddressResource = null
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->converter = $converter;
        $this->addressRepository = $addressRepository;
        $this->totalsCollector = $totalsCollector;
        $this->addressFactory = $addressFactory ?: ObjectManager::getInstance()
            ->get(AddressInterfaceFactory::class);
        $this->quoteAddressResource = $quoteAddressResource ?: ObjectManager::getInstance()
            ->get(QuoteAddressResource::class);
    }

    /**
     * {@inheritDoc}
     */
    public function get($cartId)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        /** @var \Magento\Quote\Model\Quote\Address $shippingAddress */
        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress->getCountryId()) {
            throw new StateException(__('The shipping address is missing. Set the address and try again.'));
        }

        $shippingMethod = $shippingAddress->getShippingMethod();
        if (!$shippingMethod) {
            return null;
        }

        $shippingAddress->collectShippingRates();
        /** @var \Magento\Quote\Model\Quote\Address\Rate $shippingRate */
        $shippingRate = $shippingAddress->getShippingRateByCode($shippingMethod);
        if (!$shippingRate) {
            return null;
        }
        return $this->converter->modelToDataObject($shippingRate, $quote->getQuoteCurrencyCode());
    }

    /**
     * {@inheritDoc}
     */
    public function getList($cartId)
    {
        $output = [];

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }

        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress->getCountryId()) {
            throw new StateException(__('The shipping address is missing. Set the address and try again.'));
        }
        $shippingAddress->collectShippingRates();
        $shippingRates = $shippingAddress->getGroupedAllShippingRates();
        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $output[] = $this->converter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());
            }
        }
        return $output;
    }

    /**
     * {@inheritDoc}
     */
    public function set($cartId, $carrierCode, $methodCode)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        try {
            $this->apply($cartId, $carrierCode, $methodCode);
        } catch (\Exception $e) {
            throw $e;
        }

        try {
            $this->quoteRepository->save($quote->collectTotals());
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('The shipping method can\'t be set. %1', $e->getMessage()));
        }
        return true;
    }

    /**
     * @param int $cartId The shopping cart ID.
     * @param string $carrierCode The carrier code.
     * @param string $methodCode The shipping method code.
     * @return void
     * @throws InputException The shipping method is not valid for an empty cart.
     * @throws NoSuchEntityException CThe Cart includes virtual product(s) only, so a shipping address is not used.
     * @throws StateException The billing or shipping address is not set.
     * @throws \Exception
     */
    public function apply($cartId, $carrierCode, $methodCode)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (0 == $quote->getItemsCount()) {
            throw new InputException(
                __('The shipping method can\'t be set for an empty cart. Add an item to cart and try again.')
            );
        }
        if ($quote->isVirtual()) {
            throw new NoSuchEntityException(
                __('The Cart includes virtual product(s) only, so a shipping address is not used.')
            );
        }
        $shippingAddress = $quote->getShippingAddress();
        if (!$shippingAddress->getCountryId()) {
            // Remove empty quote address
            $this->quoteAddressResource->delete($shippingAddress);
            throw new StateException(__('The shipping address is missing. Set the address and try again.'));
        }
        $shippingAddress->setShippingMethod($carrierCode . '_' . $methodCode);
    }

    /**
     * {@inheritDoc}
     */
    public function estimateByAddress($cartId, \Magento\Quote\Api\Data\EstimateAddressInterface $address)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }

        return $this->getShippingMethods($quote, $address);
    }

    /**
     * @inheritdoc
     */
    public function estimateByExtendedAddress($cartId, AddressInterface $address)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }
        return $this->getShippingMethods($quote, $address);
    }

    /**
     * {@inheritDoc}
     */
    public function estimateByAddressId($cartId, $addressId)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }
        $address = $this->addressRepository->getById($addressId);

        return $this->getShippingMethods($quote, $address);
    }

    /**
     * Get estimated rates
     *
     * @param Quote $quote
     * @param int $country
     * @param string $postcode
     * @param int $regionId
     * @param string $region
     * @param \Magento\Framework\Api\ExtensibleDataInterface|null $address
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[] An array of shipping methods.
     * @deprecated 100.2.0
     */
    protected function getEstimatedRates(
        \Magento\Quote\Model\Quote $quote,
        $country,
        $postcode,
        $regionId,
        $region,
        $address = null
    ) {
        if (!$address) {
            $address = $this->getAddressFactory()->create()
                ->setCountryId($country)
                ->setPostcode($postcode)
                ->setRegionId($regionId)
                ->setRegion($region);
        }
        return $this->getShippingMethods($quote, $address);
    }

    /**
     * Get list of available shipping methods
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Framework\Api\ExtensibleDataInterface $address
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[]
     */
    private function getShippingMethods(Quote $quote, $address)
    {
        $output = [];
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->addData($this->extractAddressData($address));
        $shippingAddress->setCollectShippingRates(true);

        $this->totalsCollector->collectAddressTotals($quote, $shippingAddress);
        $shippingRates = $shippingAddress->getGroupedAllShippingRates();
        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $output[] = $this->converter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());
            }
        }
        return $output;
    }

    /**
     * Get transform address interface into Array
     *
     * @param \Magento\Framework\Api\ExtensibleDataInterface  $address
     * @return array
     */
    private function extractAddressData($address)
    {
        $className = \Magento\Customer\Api\Data\AddressInterface::class;
        if ($address instanceof \Magento\Quote\Api\Data\AddressInterface) {
            $className = \Magento\Quote\Api\Data\AddressInterface::class;
        } elseif ($address instanceof EstimateAddressInterface) {
            $className = EstimateAddressInterface::class;
        }
        return $this->getDataObjectProcessor()->buildOutputDataArray(
            $address,
            $className
        );
    }

    /**
     * Gets the data object processor
     *
     * @return \Magento\Framework\Reflection\DataObjectProcessor
     * @deprecated 100.2.0
     */
    private function getDataObjectProcessor()
    {
        if ($this->dataProcessor === null) {
            $this->dataProcessor = ObjectManager::getInstance()
                ->get(DataObjectProcessor::class);
        }
        return $this->dataProcessor;
    }
}

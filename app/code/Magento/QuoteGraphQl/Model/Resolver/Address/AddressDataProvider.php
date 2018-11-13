<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Resolver\Address;

use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address as QuoteAddress;

/**
 * Class AddressDataProvider
 *
 * Collect and return information about cart shipping and billing addresses
 */
class AddressDataProvider
{
    /**
     * @var ExtensibleDataObjectConverter
     */
    private $dataObjectConverter;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * AddressDataProvider constructor.
     *
     * @param ExtensibleDataObjectConverter $dataObjectConverter
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        ExtensibleDataObjectConverter $dataObjectConverter,
        CartRepositoryInterface $cartRepository
    ) {
        $this->dataObjectConverter = $dataObjectConverter;
        $this->cartRepository = $cartRepository;
    }

    /**
     * Collect and return information about shipping and billing addresses
     *
     * @param CartInterface $cart
     * @return array
     */
    public function getCartAddresses(CartInterface $cart): array
    {
        $cart = $this->cartRepository->get($cart->getId());
        $addressData = [];
        $shippingAddress = $cart->getAllShippingAddresses();
        $billingAddress = $cart->getBillingAddress();

        if ($shippingAddress) {
            $shippingData = $this->dataObjectConverter->toFlatArray($shippingAddress, [], AddressInterface::class);
            $shippingData['address_type'] = 'SHIPPING';
            $addressData[] = array_merge($shippingData, $this->extractAddressData($shippingAddress));
        }

        if ($billingAddress) {
            $billingData = $this->dataObjectConverter->toFlatArray($billingAddress, [], AddressInterface::class);
            $billingData['address_type'] = 'BILLING';
            $addressData[] = array_merge($billingData, $this->extractAddressData($billingAddress));
        }

        return $addressData;
    }

    /**
     * Extract the necessary address fields from address model
     *
     * @param QuoteAddress $address
     * @return array
     */
    private function extractAddressData(QuoteAddress $address): array
    {
        $addressData = [
            'country' => [
                'code' => $address->getCountryId(),
                'label' => $address->getCountry()
            ],
            'region' => [
                'code' => $address->getRegionCode(),
                'label' => $address->getRegion()
            ],
            'street' => $address->getStreet(),
            'selected_shipping_method' => [
                'code' => $address->getShippingMethod(),
                'label' => $address->getShippingDescription(),
                'free_shipping' => $address->getFreeShipping(),
            ],
            'items_weight' => $address->getWeight(),
            'customer_notes' => $address->getCustomerNotes()
        ];

        return $addressData;
    }
}

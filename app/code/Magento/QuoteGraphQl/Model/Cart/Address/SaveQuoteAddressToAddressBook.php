<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Cart\Address;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\Address as CustomerAddress;
use Magento\Customer\Model\Data\Customer;
use Magento\Quote\Model\Quote\Address as QuoteAddress;

/**
 * Provides saved Customer Address to address book
 */
class SaveQuoteAddressToAddressBook
{
    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;

    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;

    /**
     * @var RegionInterfaceFactory
     */
    private $regionInterfaceFactory;

    /**
     * @param AddressInterfaceFactory $addressFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param RegionInterfaceFactory $regionInterfaceFactory
     */
    public function __construct(
        AddressInterfaceFactory $addressFactory,
        AddressRepositoryInterface $addressRepository,
        RegionInterfaceFactory $regionInterfaceFactory
    ) {
        $this->addressFactory = $addressFactory;
        $this->addressRepository = $addressRepository;
        $this->regionInterfaceFactory = $regionInterfaceFactory;
    }

    /**
     * Saves Quote Address to Address Book
     *
     * @param QuoteAddress $quoteAddress
     * @param Customer $customerData
     * @return CustomerAddress
     */
    public function execute(QuoteAddress $quoteAddress, Customer $customerData): AddressInterface
    {
        /** @var AddressInterface $customerAddress */
        $customerAddress = $this->addressFactory->create();
        $customerAddress->setFirstname($quoteAddress->getFirstname())
            ->setLastname($quoteAddress->getLastname())
            ->setCountryId($quoteAddress->getCountryId())
            ->setCompany($quoteAddress->getCompany())
            ->setRegionId($quoteAddress->getRegionId())
            ->setCity($quoteAddress->getCity())
            ->setPostcode($quoteAddress->getPostcode())
            ->setStreet($quoteAddress->getStreet())
            ->setTelephone($quoteAddress->getTelephone())
            ->setCustomerId($customerData->getId());

        /** @var RegionInterface $region */
        $region = $this->regionInterfaceFactory->create();
        $region->setRegionCode($quoteAddress->getRegionCode())
            ->setRegion($quoteAddress->getRegion())
            ->setRegionId($quoteAddress->getRegionId());
        $customerAddress->setRegion($region);

        $this->addressRepository->save($customerAddress);
        return $customerAddress;
    }
}

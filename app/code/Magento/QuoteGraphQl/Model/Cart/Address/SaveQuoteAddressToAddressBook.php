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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
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
    private $regionFactory;

    /**
     * @param AddressInterfaceFactory $addressFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param RegionInterfaceFactory $regionFactory
     */
    public function __construct(
        AddressInterfaceFactory $addressFactory,
        AddressRepositoryInterface $addressRepository,
        RegionInterfaceFactory $regionFactory
    ) {
        $this->addressFactory = $addressFactory;
        $this->addressRepository = $addressRepository;
        $this->regionFactory = $regionFactory;
    }

    /**
     * Save Quote Address to Address Book
     *
     * @param QuoteAddress $quoteAddress
     * @param int $customerId
     * @return void
     * @throws GraphQlInputException
     */
    public function execute(QuoteAddress $quoteAddress, int $customerId): void
    {
        try {
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
                ->setCustomerId($customerId);

            /** @var RegionInterface $region */
            $region = $this->regionFactory->create();
            $region->setRegionCode($quoteAddress->getRegionCode())
                ->setRegion($quoteAddress->getRegion())
                ->setRegionId($quoteAddress->getRegionId());
            $customerAddress->setRegion($region);

            $this->addressRepository->save($customerAddress);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }
    }
}

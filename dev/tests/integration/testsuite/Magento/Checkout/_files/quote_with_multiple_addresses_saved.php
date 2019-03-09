<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require 'quote_with_address_saved.php';
require __DIR__ . '/../../Customer/_files/customer_two_addresses.php';

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Quote\Model\Quote\Address $quoteSecondShippingAddress */
$quoteSecondShippingAddress = $objectManager->create(\Magento\Quote\Model\Quote\Address::class);

$quoteSecondShippingAddress->importCustomerAddressData($addressRepository->getById(2));

$quote->setStoreId(
    1
)->setIsActive(
    true
)->setIsMultiShipping(
    true
)->setShippingAddress(
    $quoteSecondShippingAddress
);

$quoteRepository->save($quote);
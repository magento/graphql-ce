<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require 'quote_with_simple_product_saved.php';

/** @var $objectManager \Magento\TestFramework\ObjectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Quote\Model\Quote\Address $quoteShippingAddress */
$quoteShippingAddress = $objectManager->create(\Magento\Quote\Model\Quote\Address::class);
$quoteShippingAddress->isObjectNew(true);
$quoteShippingAddress->setData(
    [
        'attribute_set_id' => 2,
        'telephone' => 3468676,
        'postcode' => 75477,
        'country_id' => 'US',
        'city' => 'CityM',
        'company' => 'CompanyName',
        'street' => 'Green str, 67',
        'lastname' => 'Smith',
        'firstname' => 'John',
        'parent_id' => 1,
        'region_id' => 1,
    ]
);

/** @var \Magento\Quote\Model\Quote\Address $quoteSecondShippingAddressShippingAddress */
$quoteSecondShippingAddress = $objectManager->create(\Magento\Quote\Model\Quote\Address::class);
$quoteSecondShippingAddress->isObjectNew(true);
$quoteSecondShippingAddress->setData(
    [
        'attribute_set_id' => 2,
        'telephone' => 3234676,
        'postcode' => 47676,
        'country_id' => 'US',
        'city' => 'CityX',
        'street' => ['Black str, 48'],
        'lastname' => 'Smith',
        'firstname' => 'Mia',
        'parent_id' => 1,
        'region_id' => 1,
    ]
);
/** @var \Magento\Quote\Model\Quote $quote */
$quote->setIsMultiShipping(true)
    ->setReservedOrderId('test_order_with_simple_product_multiple_addresses')
    ->setShippingAddress($quoteShippingAddress)
    ->setShippingAddress($quoteSecondShippingAddress)
    ->setCustomerEmail($quote->getCustomerEmail())
    ->addProduct($product->load($product->getId()), 1);

$quote->collectTotals()->save();

/** @var \Magento\Quote\Model\QuoteIdMask $quoteIdMask */
$quoteIdMask->setQuoteId($quote->getId());
$quoteIdMask->setDataChanges(true);
$quoteIdMask->save();

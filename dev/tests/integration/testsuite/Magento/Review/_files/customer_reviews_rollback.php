<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/** @var $objectManager \Magento\TestFramework\ObjectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$reviewResource = $objectManager->get(\Magento\Review\Model\ResourceModel\Review::class);
$collectionFactory = $objectManager->get(\Magento\Review\Model\ResourceModel\Review\CollectionFactory::class);

$reviewTitles = [
    'GraphQl: Empty Customer Review Summary',
    'GraphQl: Not Approved Review Summary',
    'GraphQl: Approved Review Summary',
    'GraphQl: Secondary Approved Review Summary',
    'GraphQl: Pending Review Summary'
];

$collection = $collectionFactory->create()
    ->addFieldToFilter('title', ['in' => $reviewTitles]);

foreach ($collection as $review) {
    $reviewResource->delete($review);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

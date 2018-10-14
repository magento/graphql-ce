<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require __DIR__ . '/../../../Magento/Customer/_files/customer.php';
require __DIR__ . '/../../../Magento/Catalog/_files/product_simple.php';

\Magento\TestFramework\Helper\Bootstrap::getInstance()->loadArea(
    \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE
);

$reviewData = [
    [
        'customer_id' => null,
        'title' => 'Empty Customer Review Summary',
        'detail' => 'Review text',
        'nickname' => 'Nickname',
        'status_id' => \Magento\Review\Model\Review::STATUS_PENDING
    ],
    [
        'customer_id' => $customer->getId(),
        'title' => 'Not Approved Review Summary',
        'detail' => 'Review text',
        'nickname' => 'Nickname',
        'status_id' => \Magento\Review\Model\Review::STATUS_NOT_APPROVED,
    ],
    [
        'customer_id' => $customer->getId(),
        'title' => 'Approved Review Summary',
        'detail' => 'Review text',
        'nickname' => 'Nickname',
        'status_id' => \Magento\Review\Model\Review::STATUS_APPROVED
    ],
    [
        'customer_id' => $customer->getId(),
        'title' => 'Secondary Approved Review Summary',
        'detail' => 'Review text',
        'nickname' => 'Nickname',
        'status_id' => \Magento\Review\Model\Review::STATUS_APPROVED
    ],
    [
        'customer_id' => $customer->getId(),
        'title' => 'Pending Review Summary',
        'detail' => 'Review text',
        'nickname' => 'Nickname',
        'status_id' => \Magento\Review\Model\Review::STATUS_PENDING,
    ],
];

foreach ($reviewData as $data) {
    $review = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
        \Magento\Review\Model\Review::class,
        ['data' => $data]
    );

    $review
        ->setEntityId($review->getEntityIdByCode(\Magento\Review\Model\Review::ENTITY_PRODUCT_CODE))
        ->setEntityPkValue($product->getId())
        ->setStoreId(
            \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
                \Magento\Store\Model\StoreManagerInterface::class
            )->getStore()->getId()
        )
        ->setStores([
            \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
                \Magento\Store\Model\StoreManagerInterface::class
            )->getStore()->getId()
        ])
        ->save();
}

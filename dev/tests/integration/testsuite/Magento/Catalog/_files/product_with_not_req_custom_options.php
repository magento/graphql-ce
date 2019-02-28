<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);

$product->setTypeId(
    'simple'
)->setAttributeSetId(
    4
)->setWebsiteIds(
    [1]
)->setName(
    'Simple Product With not required Custom Options 3'
)->setSku(
    'simple_not_req_custom_option'
)->setPrice(
    10
)->setMetaTitle(
    'meta title'
)->setMetaKeyword(
    'meta keyword'
)->setMetaDescription(
    'meta description'
)->setVisibility(
    \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH
)->setStatus(
    \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
)->setCanSaveCustomOptions(
    true
)->setStockData(
    [
        'qty' => 100,
        'is_in_stock' => 1,
        'manage_stock' => 1,
    ]
)->setHasOptions(true);

$options = [
    [
        'title' => 'test_option_code_1',
        'type' => 'field',
        'is_require' => false,
        'sort_order' => 1,
        'price' => -10.0,
        'price_type' => 'fixed',
        'sku' => 'sku1',
        'max_characters' => 10,
    ],
    [
        'title' => 'drop_down option',
        'type' => 'drop_down',
        'is_require' => false,
        'sort_order' => 4,
        'values' => [
            [
                'title' => 'drop_down option 1',
                'price' => 10,
                'price_type' => 'fixed',
                'sku' => 'drop_down option 1 sku',
                'sort_order' => 1,
            ],
            [
                'title' => 'drop_down option 2',
                'price' => 20,
                'price_type' => 'fixed',
                'sku' => 'drop_down option 2 sku',
                'sort_order' => 2,
            ],
        ],
    ],
    [
        'title' => 'radio option',
        'type' => 'radio',
        'is_require' => false,
        'sort_order' => 5,
        'values' => [
            [
                'title' => 'radio option 1',
                'price' => 10,
                'price_type' => 'fixed',
                'sku' => 'radio option 1 sku',
                'sort_order' => 1,
            ],
            [
                'title' => 'radio option 2',
                'price' => 20,
                'price_type' => 'fixed',
                'sku' => 'radio option 2 sku',
                'sort_order' => 2,
            ],
        ],
    ],
    [
        'title' => 'checkbox option',
        'type' => 'checkbox',
        'is_require' => false,
        'sort_order' => 6,
        'values' => [
            [
                'title' => 'checkbox option 1',
                'price' => 10,
                'price_type' => 'fixed',
                'sku' => 'checkbox option 1 sku',
                'sort_order' => 1,
            ],
            [
                'title' => 'checkbox option 2',
                'price' => 20,
                'price_type' => 'fixed',
                'sku' => 'checkbox option 2 sku',
                'sort_order' => 2,
            ],
        ],
    ],
    [
        'title' => 'multiple option',
        'type' => 'multiple',
        'is_require' => false,
        'sort_order' => 7,
        'values' => [
            [
                'title' => 'multiple option 1',
                'price' => 10,
                'price_type' => 'fixed',
                'sku' => 'multiple option 1 sku',
                'sort_order' => 1,
            ],
            [
                'title' => 'multiple option 2',
                'price' => 20,
                'price_type' => 'fixed',
                'sku' => 'multiple option 2 sku',
                'sort_order' => 2,
            ],
        ],
    ]
];

$customOptions = [];

/** @var \Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory $customOptionFactory */
$customOptionFactory = $objectManager->create(\Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory::class);

foreach ($options as $option) {
    /** @var \Magento\Catalog\Api\Data\ProductCustomOptionInterface $customOption */
    $customOption = $customOptionFactory->create(['data' => $option]);
    $customOption->setProductSku($product->getSku());

    $customOptions[] = $customOption;
}

$product->setOptions($customOptions);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryFactory */
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$productRepository->save($product);

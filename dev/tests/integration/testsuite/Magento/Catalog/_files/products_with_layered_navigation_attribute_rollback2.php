<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var \Magento\Framework\Registry $registry */
$registry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);

foreach (['nav_product_test_1', 'nav_product_test_2', 'nav_product_test_3', 'nav_product_test_4'] as $sku) {
    try {
        $product = $productRepository->get($sku, false, null, true);
        $productRepository->delete($product);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
        //Product already removed
    }
}

$productCollection = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
foreach ($productCollection as $product) {
    $product->delete();
}

/** @var $category \Magento\Catalog\Model\Category */
$category = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\Category::class);
$category->load(2333);
if ($category->getId()) {
    $category->delete();
}

$eavConfig = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Eav\Model\Config::class);
$attribute = $eavConfig->getAttribute('catalog_product', 'test_simple_nav_attr_2');
if ($attribute instanceof \Magento\Eav\Model\Entity\Attribute\AbstractAttribute
    && $attribute->getId()
) {
    $attribute->delete();
}
$eavConfig->clear();


$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

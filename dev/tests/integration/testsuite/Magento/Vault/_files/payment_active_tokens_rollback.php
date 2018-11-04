<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

/** @var \Magento\Framework\Registry $registry */
$registry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);

/** @var \Magento\Vault\Model\ResourceModel\PaymentToken $urlRewriteCollection */
$paymentTokenCollection = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\Vault\Model\ResourceModel\PaymentToken\Collection::class);
$collection = $paymentTokenCollection
    ->addFieldToFilter('public_hash', ['H123456789','H987654321','H1122334455','H5544332211'])
    ->load()
    ->walk('delete');

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

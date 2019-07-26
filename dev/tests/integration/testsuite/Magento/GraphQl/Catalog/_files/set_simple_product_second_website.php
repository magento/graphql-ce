<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\WebsiteFactory;
use Magento\Store\Model\ResourceModel\Website as WebsiteResource;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var WebsiteFactory $websiteFactory */
$websiteFactory = $objectManager->get(WebsiteFactory::class);
/** @var WebsiteResource $websiteResource */
$websiteResource = $objectManager->get(WebsiteResource::class);

$website = $websiteFactory->create();
$websiteResource->load($website, 'test', 'code');

$product = $productRepository->get('simple_product');
$product->setWebsiteIds([$website->getId()]);
$productRepository->save($product);

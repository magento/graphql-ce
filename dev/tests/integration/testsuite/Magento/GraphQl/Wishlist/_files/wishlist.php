<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

/*
 * Creates and assigns a new wishlist for a customer.
 */

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResource;

/** @var WishlistFactory\ $wishlistFactory */
$wishlistFactory = Bootstrap::getObjectManager()->get(WishlistFactory::class);
/** @var CustomerRepositoryInterface $customerRepository */
$customerRepository = Bootstrap::getObjectManager()->get(CustomerRepositoryInterface::class);
/** @var WishlistResource $wishlistResource */
$wishlistResource = Bootstrap::getObjectManager()->get(WishlistResource::class);

/** @var CustomerInterface $customer */
$customer = $customerRepository->get('customer@example.com');
/** @var Wishlist $wishlist */
$wishlist = $wishlistFactory->create();
$wishlist->setCustomerId($customer->getId());
$wishlist->setSharingCode('fixture_unique_code');
$wishlist->setShared(0);
$wishlistResource->save($wishlist);


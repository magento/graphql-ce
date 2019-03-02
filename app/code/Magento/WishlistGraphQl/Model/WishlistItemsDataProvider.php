<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory as WishlistItemCollectionFactory;
use Magento\Wishlist\Model\ResourceModel\Item\Collection as WishlistItemCollection;

class WishlistItemsDataProvider
{
    /**
     * @var WishlistItemCollectionFactory
     */
    private $wishlistItemCollectionFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param WishlistItemCollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        WishlistItemCollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->wishlistItemCollectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param int $customerId
     * @return Item[]
     */
    public function getWishlistItemsForCustomer(int $customerId): array
    {
        /** @var WishlistItemCollection $itemCollection */
        $itemCollection = $this->wishlistItemCollectionFactory->create();
        $itemCollection->addCustomerIdFilter($customerId);
        $itemCollection->addStoreFilter(array_map(function (StoreInterface $store) {
            return $store->getId();
        }, $this->storeManager->getStores()));
        $itemCollection->setVisibilityFilter();
        $itemCollection->load();
        return $itemCollection->getItems();
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model;

use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResource;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;

/**
 * Get wishlist for the specified customer
 */
class GetWishlistForCustomer
{
    /**
     * @var WishlistResource
     */
    private $wishlistResource;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @param WishlistResource $wishlistResource
     * @param WishlistFactory $wishlistFactory
     */
    public function __construct(
        WishlistResource $wishlistResource,
        WishlistFactory $wishlistFactory
    ) {
        $this->wishlistResource = $wishlistResource;
        $this->wishlistFactory = $wishlistFactory;
    }

    /**
     * Get wishlist for the specified customer
     *
     * @param int $customerId
     * @return Wishlist
     */
    public function execute(int $customerId)
    {
        /** @var Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($wishlist, $customerId, 'customer_id');

        return $wishlist;
    }
}

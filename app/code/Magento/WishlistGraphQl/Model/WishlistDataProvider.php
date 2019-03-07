<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model;

use Magento\Wishlist\Model\ResourceModel\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class WishlistDataProvider
{
    /**
     * @var Wishlist
     */
    private $wishlistResource;
    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @param Wishlist $wishlistResource
     * @param WishlistFactory $wishlistFactory
     */
    public function __construct(
        Wishlist $wishlistResource,
        WishlistFactory $wishlistFactory
    ) {
        $this->wishlistResource = $wishlistResource;
        $this->wishlistFactory = $wishlistFactory;
    }

    /**
     * @param int $customerId
     * @return \Magento\Wishlist\Model\Wishlist
     * @throws GraphQlInputException
     */
    public function getWishlistForCustomer(int $customerId): \Magento\Wishlist\Model\Wishlist
    {
        /** @var \Magento\Wishlist\Model\Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($wishlist, $customerId, 'customer_id');

        if (!$wishlist || !$wishlist->getId()) {
            throw new GraphQlInputException(__('Cannot get a wish list for the specified Customer ID'));
        }

        return $wishlist;
    }
}

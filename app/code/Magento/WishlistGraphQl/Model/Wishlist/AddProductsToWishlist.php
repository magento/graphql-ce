<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model\Wishlist;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Message\AbstractMessage;
use Magento\Quote\Model\Quote;
use Magento\Wishlist\Model\Wishlist;
use Magento\WishlistGraphQl\Model\Wishlist\AddSimpleProductToWishlist;

class AddProductsToWishlist
{
    /**
     * @var \Magento\WishlistGraphQl\Model\Wishlist\AddSimpleProductToWishlist
     */
    private $addSimpleProductToWishlist;

    /**
     * AddProductsToWishlist constructor.
     * @param \Magento\WishlistGraphQl\Model\Wishlist\AddSimpleProductToWishlist $addSimpleProductToWishlist
     */
    public function __construct(
        AddSimpleProductToWishlist $addSimpleProductToWishlist
    ) {
        $this->addSimpleProductToWishlist = $addSimpleProductToWishlist;
    }

    /**
     * Add products to wishlist
     *
     * @param Wishlist $wishlist
     * @param array $wishlistItems
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function execute(Wishlist $wishlist, array $wishlistItems): void
    {
        foreach ($wishlistItems as $wishlistItemData) {
            $this->addSimpleProductToWishlist->execute($wishlist, $wishlistItemData);
        }

    }
}

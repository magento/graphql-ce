<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;

class GetWishlistByAttribute
{

    /**
     * @var WishlistResourceModel
     */
    private $wishlistResource;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @param WishlistResourceModel $wishlistResource
     * @param WishlistFactory $wishlistFactory
     */
    public function __construct(
        WishlistResourceModel $wishlistResource,
        WishlistFactory $wishlistFactory
    )
    {
        $this->wishlistResource = $wishlistResource;
        $this->wishlistFactory = $wishlistFactory;
    }

    /**
     * Loads Wishlist by the attribute name and value
     *
     * @param string $attribute
     * @param mixed $value
     * @param bool $create
     * @return Wishlist
     * @throws AlreadyExistsException
     */
    public function execute(string $attribute, $value, bool $create = false)
    {
        /** @var Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($wishlist, $value, $attribute);
        if (!$wishlist->getId() && $create) {
            $wishlist->setData($attribute, $value);
            $wishlist->generateSharingCode();
            $this->wishlistResource->save($wishlist);
        }
        return $wishlist;
    }
}

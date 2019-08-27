<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Wishlist\Model\Wishlist;
use Magento\WishlistGraphQl\Model\Wishlist\AddProductsToWishlist;
use Magento\WishlistGraphQl\Model\Wishlist\GetWishlistForUser;

/**
 * Add simple products to cart GraphQl resolver
 * {@inheritdoc}
 */
class AddSimpleProductsToWishlist implements ResolverInterface
{
    /**
     * @var GetWishlistForUser
     */
    private $getWishlistForUser;

    /**
     * @var AddProductsToWishlist
     */
    private $addProductsToWishlist;

    /**
     * @param GetWishlistForUser $getWishlistForUser
     * @param AddProductsToWishlist $addProductsToWishlist
     */
    public function __construct(
        GetWishlistForUser $getWishlistForUser,
        AddProductsToWishlist $addProductsToWishlist
    ) {
        $this->getWishlistForUser = $getWishlistForUser;
        $this->addProductsToWishlist = $addProductsToWishlist;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {

        /** @var Wishlist $wishlist */
        $wishlist = $this->getWishlistForUser->execute($context);

        $wishlistItems = $args['input']['wishlist_items'];

        if (null === $wishlist->getId()) {
            try {
                $customerId = $context->getUserId();
                $wishlist->setCustomerId($customerId);
                $wishlist->save();
            } catch (\Exception $e) {
                throw new GraphQlInputException(
                    __(
                        'Could not create new wish list: %message',
                        [ 'message' => $e->getMessage()]
                    )
                );
            }
        }
        $this->addProductsToWishlist->execute($wishlist, $wishlistItems);

        return [
            'sharing_code' => $wishlist->getSharingCode(),
            'updated_at' => $wishlist->getUpdatedAt(),
            'items_count' => $wishlist->getItemsCount(),
            'name' => $wishlist->getName(),
            'model' => $wishlist,
        ];
    }
}

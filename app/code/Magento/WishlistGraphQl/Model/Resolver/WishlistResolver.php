<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Wishlist\Model\Wishlist;
use Magento\WishlistGraphQl\Model\Wishlist\GetWishlistForUser;

/**
 * Fetches the Wishlist data according to the GraphQL schema
 */
class WishlistResolver implements ResolverInterface
{
    /**
     * @var GetWishlistForUser
     */
    private $getWishlistForUser;

    /**
     * @param GetWishlistForUser $getWishlistForUser
     */
    public function __construct(GetWishlistForUser $getWishlistForUser)
    {
        $this->getWishlistForUser = $getWishlistForUser;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {

        /** @var Wishlist $wishlist */
        $wishlist = $this->getWishlistForUser->execute($context);

        if (null === $wishlist->getId()) {
            throw new GraphQlNoSuchEntityException(__('The current user does not have a wishlist'));
        }

        return [
            'sharing_code' => $wishlist->getSharingCode(),
            'updated_at' => $wishlist->getUpdatedAt(),
            'items_count' => $wishlist->getItemsCount(),
            'name' => $wishlist->getName(),
            'model' => $wishlist,
        ];
    }
}

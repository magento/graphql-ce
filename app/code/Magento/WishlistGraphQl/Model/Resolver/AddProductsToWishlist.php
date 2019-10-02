<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\WishlistGraphQl\Model\AddItemsToWishlist as AddItemsToWishlistService;

/**
 * @inheritdoc
 */
class AddProductsToWishlist implements ResolverInterface
{
    /**
     * @var AddItemsToWishlistService
     */
    private $addItemsToWishlistService;

    /**
     * @param AddItemsToWishlistService $addItemsToWishlistService
     */
    public function __construct(
        AddItemsToWishlistService $addItemsToWishlistService
    ) {
        $this->addItemsToWishlistService = $addItemsToWishlistService;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($args['input']['wishlist_items']) || count($args['input']['wishlist_items']) < 1) {
            throw new GraphQlInputException(__('Required parameter "wishlist_items" is missing'));
        }

        $customerId = $context->getUserId();
        if ($customerId === 0) {
            throw new GraphQlAuthorizationException(__('You must be logged in to use wishlist'));
        }
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $wishlist = $this->addItemsToWishlistService->execute($customerId, $args['input']['wishlist_items'], $storeId);

        return [
                'model' => $wishlist,
                'sharing_code' => $wishlist->getSharingCode(),
                'updated_at' => $wishlist->getUpdatedAt(),
                'items_count' => $wishlist->getItemsCount(),
                'name' => $wishlist->getName()
        ];
    }
}

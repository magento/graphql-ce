<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\WishlistGraphQl\Model\GetWishlistByAttribute;

/**
 * Fetches customer wishlist data
 */
class CustomerWishlistResolver implements ResolverInterface
{

    /**
     * @var GetWishlistByAttribute
     */
    private $getWishlistByAttribute;

    /**
     * @param GetWishlistByAttribute $getWishlistByAttribute
     */
    public function __construct(GetWishlistByAttribute $getWishlistByAttribute)
    {
        $this->getWishlistByAttribute = $getWishlistByAttribute;
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
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $wishlist = $this->getWishlistByAttribute->execute('customer_id', $context->getUserId(), true);

        return [
            'id' => (string) $wishlist->getId(),
            'sharing_code' => $wishlist->getSharingCode(),
            'updated_at' => $wishlist->getUpdatedAt(),
            'items_count' => $wishlist->getItemsCount(),
            'model' => $wishlist,
        ];
    }
}

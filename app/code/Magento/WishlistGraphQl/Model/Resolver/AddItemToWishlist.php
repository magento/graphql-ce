<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model\Resolver;

use Magento\Catalog\Model\Product;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\WishlistGraphQl\Model\WishlistDataProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Wishlist\Model\Wishlist;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * @inheritdoc
 */
class AddItemToWishlist implements ResolverInterface
{
    /**
     * @var WishlistProviderInterface
     */
    private $wishlistProvider;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var WishlistDataProvider
     */
    private $wishlistDataProvider;

    /**
     * @param WishlistProviderInterface $wishlistProvider
     * @param ProductRepositoryInterface $productRepository
     * @param WishlistDataProvider $wishlistDataProvider
     */
    public function __construct(
        WishlistProviderInterface $wishlistProvider,
        ProductRepositoryInterface $productRepository,
        WishlistDataProvider $wishlistDataProvider
    ) {
        $this->wishlistProvider = $wishlistProvider;
        $this->productRepository = $productRepository;
        $this->wishlistDataProvider = $wishlistDataProvider;
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
        if (!isset($args['input']['skus'])) {
            throw new GraphQlInputException(__('You must specify at least one "sku" value'));
        }
        $wishlist = $this->wishlistDataProvider->getWishlistForCustomer($context->getUserId());
        if (!$wishlist || !$wishlist->getId()) {
            throw new GraphQlInputException(__('Cannot get a wish list for the specified Customer ID'));
        }
        $this->addMultipleProducts($args['input']['skus'], $wishlist);
        return [
            'wishlist' => [
                'sharing_code' => $wishlist->getSharingCode(),
                'updated_at' => $wishlist->getUpdatedAt()
            ]
        ];
    }

    /**
     * @param array $skus
     * @param Wishlist $wishList
     * @return Wishlist
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addMultipleProducts($skus, Wishlist $wishList)
    {
        foreach ($skus as $sku) {
            /** @var Product $product */
            $product = $this->productRepository->get($sku);
            $wishList->addNewItem($product);
        }
        return $wishList;
    }
}

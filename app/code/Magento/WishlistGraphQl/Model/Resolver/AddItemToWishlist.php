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
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessor\StockProcessor;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

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
     * @var SearchCriteriaInterface
     */
    private $searchCriteria;
    /**
     * @var Visibility
     */
    private $catalogProductVisibility;
    /**
     * @var StockProcessor
     */
    private $stockProcessor;
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @param WishlistProviderInterface $wishlistProvider
     * @param ProductRepositoryInterface $productRepository
     * @param WishlistDataProvider $wishlistDataProvider
     * @param SearchCriteriaInterface $searchCriteria
     * @param Visibility $catalogProductVisibility
     * @param StockProcessor $stockProcessor
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(
        WishlistProviderInterface $wishlistProvider,
        ProductRepositoryInterface $productRepository,
        WishlistDataProvider $wishlistDataProvider,
        SearchCriteriaInterface $searchCriteria,
        Visibility $catalogProductVisibility,
        StockProcessor $stockProcessor,
        CollectionFactory $productCollectionFactory
    ) {
        $this->wishlistProvider = $wishlistProvider;
        $this->productRepository = $productRepository;
        $this->wishlistDataProvider = $wishlistDataProvider;
        $this->searchCriteria = $searchCriteria;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->stockProcessor = $stockProcessor;
        $this->productCollectionFactory = $productCollectionFactory;
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
     * @param $skus
     * @param Wishlist $wishList
     *
     * @return Wishlist
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addMultipleProducts($skus, Wishlist $wishList)
    {
        $productCollection = $this->getProductCollectionBySkus($skus);
        if ($productCollection->getSize()) {
            foreach ($productCollection as $product) {
                /** @var Product $product */
                $wishList->addNewItem($product);
            }
        }
        return $wishList;
    }

    /**
     * @param array $skus
     * @return Collection
     */
    private function getProductCollectionBySkus($skus)
    {
        /** @var Collection $productsCollection */
        $productsCollection = $this->productCollectionFactory->create();
        $productsCollection->setVisibility($this->catalogProductVisibility->getVisibleInSiteIds());
        $productsCollection->addAttributeToFilter('sku', ['in' => $skus]);
        return $productsCollection;
    }
}

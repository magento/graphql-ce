<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model\Resolver;

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\WishlistGraphQl\Model\GetWishlistForCustomer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Api\Data\ProductInterface;

// TODO: split resolver into smaller parts

/**
 * @inheritdoc
 */
class AddItemsToWishlist implements ResolverInterface
{
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GetWishlistForCustomer
     */
    private $getWishlistForCustomer;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @param GetWishlistForCustomer $getWishlistForCustomer
     * @param ProductCollectionFactory $productCollectionFactory
     * @param DataObjectFactory $dataObjectFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        GetWishlistForCustomer $getWishlistForCustomer,
        ProductCollectionFactory $productCollectionFactory,
        DataObjectFactory $dataObjectFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->getWishlistForCustomer = $getWishlistForCustomer;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->dataObjectFactory = $dataObjectFactory;

        // TODO: use store id from context instead when https://github.com/magento/graphql-ce/pull/493 is merged
        $this->storeManager = $storeManager;
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

        $wishlist = $this->getWishlistForCustomer->execute($customerId);

        $productsList = [];
        foreach ($args['input']['wishlist_items'] as $wishlistItem) {
            $productsList[$wishlistItem['sku']] = $wishlistItem['quantity'] ?? 1;
        }

        $products = $this->getAvailableProductsBySku(array_keys($productsList));
        if (count($products) === 0) {
            throw new GraphQlInputException(__('Cannot add the specified items to wishlist'));
        }

        $errors = [];

        /** @var ProductInterface $product */
        foreach ($products as $product) {
            $buyRequest = $this->createBuyRequest($productsList[$product->getSku()]);
            $item = $wishlist->addNewItem($product, $buyRequest);

            /* The system returns string in case of an error */
            if (is_string($item)) {
                $errors[] = $item;
            }
        }

        if (count($errors) > 0) {
            throw new GraphQlInputException(__(implode("\n", $errors)));
        }

        return [
            'wishlist' => [
                'sharing_code' => $wishlist->getSharingCode(),
                'updated_at' => $wishlist->getUpdatedAt(),
                'items_count' => $wishlist->getItemsCount(),
                'name' => $wishlist->getName(),
                'model' => $wishlist
            ]
        ];
    }


    /**
     * Returns available products for current store according to the SKU list
     *
     * @param array $sku_list
     * @return array
     */
    private function getAvailableProductsBySku(array $sku_list): array
    {
        return $this->productCollectionFactory->create()
            ->addAttributeToFilter('sku', ['in' => $sku_list])
            ->setStoreId($this->storeManager->getStore()->getId())
            ->setVisibility(
                [Visibility::VISIBILITY_BOTH, Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_IN_SEARCH]
            )
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->getItems();
    }

    /**
     * Creates buy request
     *
     * @param float $qty
     * @return DataObject
     */
    private function createBuyRequest(float $qty): DataObject
    {
        return $this->dataObjectFactory->create(
            [
                'data' => [
                    'qty' => $qty,
                ],
            ]
        );
    }
}

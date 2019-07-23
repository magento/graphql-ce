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
use Magento\WishlistGraphQl\Model\GetWishlistForCustomer;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

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
     * @param GetWishlistForCustomer $getWishlistForCustomer
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        GetWishlistForCustomer $getWishlistForCustomer,
        ProductCollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->getWishlistForCustomer = $getWishlistForCustomer;
        $this->productCollectionFactory = $productCollectionFactory;
        // TODO: use store id from context instead when https://github.com/magento/graphql-ce/pull/493 is merged
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($args['input']['sku_list']) || count($args['input']['sku_list']) < 1) {
            throw new GraphQlInputException(__('Required parameter "sku_list" is missing'));
        }

        $customerId = $context->getUserId();
        if ($customerId === 0) {
            throw new GraphQlAuthorizationException(__('You must be logged in to use wishlist'));
        }

        $wishlist = $this->getWishlistForCustomer->execute($customerId);

        $products = $this->getAvailableProductsBySku($args['input']['sku_list']);
        if (count($products) === 0) {
            throw new GraphQlInputException(__('Cannot add the specified items to wishlist'));
        }

        $errors = [];

        foreach ($products as $product) {
            $item = $wishlist->addNewItem($product);

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
}

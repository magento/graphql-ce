<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Wishlist\Model\Wishlist;

/**
 * Adds items to the wishlist if the corresponding products are available
 */
class AddItemsToWIshlist
{
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var GetWishlistForCustomer
     */
    private $getWishlistForCustomer;

    /**
     * @param GetWishlistForCustomer $getWishlistForCustomer
     * @param ProductCollectionFactory $productCollectionFactory
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        GetWishlistForCustomer $getWishlistForCustomer,
        ProductCollectionFactory $productCollectionFactory,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->getWishlistForCustomer = $getWishlistForCustomer;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Adds items to the wishlist if the corresponding products are available
     *
     * @param int $customerId
     * @param array $items
     * @param int $storeId
     * @return Wishlist
     * @throws GraphQlInputException
     */
    public function execute(int $customerId, array $items, int $storeId): Wishlist
    {
        $wishlist = $this->getWishlistForCustomer->execute($customerId);

        $productsList = [];
        foreach ($items as $item) {
            $productsList[$item['sku']] = $item['quantity'] ?? 1;
        }

        $products = $this->getAvailableProductsBySku(array_keys($productsList), $storeId);
        if (count($products) === 0) {
            throw new GraphQlInputException(__('Cannot add the specified items to wishlist'));
        }

        $errors = [];

        try {
            /** @var ProductInterface $product */
            foreach ($products as $product) {
                $buyRequest = $this->createBuyRequest($productsList[$product->getSku()]);
                $item = $wishlist->addNewItem($product, $buyRequest);

                /* The system returns string in case of an error */
                if (is_string($item)) {
                    $errors[] = $item;
                }
            }
        } catch (LocalizedException $exception) {
            throw new GraphQlInputException(__($exception->getMessage()), $exception);
        }

        if (count($errors) > 0) {
            throw new GraphQlInputException(__(implode("\n", $errors)));
        }

        return $wishlist;
    }

    /**
     * Returns available products for current store according to the SKU list
     *
     * @param array $skuList
     * @param int $storeId
     * @return array
     */
    private function getAvailableProductsBySku(array $skuList, int $storeId): array
    {
        return $this->productCollectionFactory->create()
            ->addAttributeToFilter('sku', ['in' => $skuList])
            ->setStoreId($storeId)
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

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

/**
 * Class GetAvailableProductsBySkuList
 *
 * Returns available products for the specified store according to the SKU list
 */
class GetAvailableProductsBySkuList
{
    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * GetAvailableProductsBySkuList constructor.
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Returns available products for the specified store according to the SKU list
     *
     * @param array $skuList
     * @param int $storeId
     * @return array
     */
    public function execute(array $skuList, int $storeId): array
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
}

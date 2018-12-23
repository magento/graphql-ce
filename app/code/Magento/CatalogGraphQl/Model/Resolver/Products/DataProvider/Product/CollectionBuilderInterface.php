<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Interface CollectionBuilderInterface
 * @package Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product
 */
interface CollectionBuilderInterface
{
    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @param array $attributeNames
     * @param bool $isSearch
     * @param bool $isChildSearch
     * @return Collection
     */
    public function build(
        SearchCriteriaInterface $searchCriteria,
        array $attributeNames,
        bool $isSearch = false,
        bool $isChildSearch = false
    ): Collection;
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Categories\DataProvider\Category;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Add additional joins, attributes, and clauses to a category collection.
 */
interface CollectionProcessorInterface
{
    /**
     * Process collection to add additional joins, attributes, and clauses to a category collection.
     *
     * @param Collection $collection
     * @param SearchCriteriaInterface $searchCriteria
     * @param array $attributeNames
     * @return Collection
     */
    public function process(
        Collection $collection,
        SearchCriteriaInterface $searchCriteria,
        array $attributeNames
    ): Collection;
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Categories\DataProvider\Category\CollectionProcessor;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\CatalogGraphQl\Model\Resolver\Categories\DataProvider\Category\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Adds passed in attributes to category collection results
 *
 * {@inheritdoc}
 */
class AttributeProcessor implements CollectionProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(
        Collection $collection,
        SearchCriteriaInterface $searchCriteria,
        array $attributeNames
    ): Collection {
        foreach ($attributeNames as $name) {
            $collection->addAttributeToSelect($name);
        }

        return $collection;
    }
}

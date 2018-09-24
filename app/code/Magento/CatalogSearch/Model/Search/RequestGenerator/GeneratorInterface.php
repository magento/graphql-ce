<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogSearch\Model\Search\RequestGenerator;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

/**
 * @api
 * @since 100.1.6
 * @deprecated CatalogSearch will be removed in 2.4, and {@see \Magento\ElasticSearch}
 *             will replace it as the default search engine.
 */
interface GeneratorInterface
{
    /**
     * Get filter data for specific attribute
     * @param Attribute $attribute
     * @param string $filterName
     * @return array
     * @since 100.1.6
     */
    public function getFilterData(Attribute $attribute, $filterName);

    /**
     * Get aggregation data for specific attribute
     * @param Attribute $attribute
     * @param string $bucketName
     * @return array
     * @since 100.1.6
     */
    public function getAggregationData(Attribute $attribute, $bucketName);
}

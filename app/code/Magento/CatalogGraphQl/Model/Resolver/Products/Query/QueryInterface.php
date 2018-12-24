<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Products\Query;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResult;

/**
 * Interface for Product query types
 */
interface QueryInterface
{
    public function getResult(
        SearchCriteriaInterface $searchCriteria,
        ResolveInfo $info,
        array $arguments = [],
        bool $isSearch = false
    ): SearchResult;

    public function getLayerType(): string;
}

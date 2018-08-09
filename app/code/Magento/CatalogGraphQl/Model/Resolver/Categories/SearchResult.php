<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Categories;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Container for a category search holding the item result and the array in the GraphQL-readable category type format.
 */
class SearchResult
{
    /**
     * @var SearchResultsInterface
     */
    private $totalCount;

    /**
     * @var array
     */
    private $categoriesSearchResult;

    /**
     * @param int $totalCount
     * @param array $categoriesSearchResult
     */
    public function __construct(int $totalCount, array $categoriesSearchResult)
    {
        $this->totalCount = $totalCount;
        $this->categoriesSearchResult = $categoriesSearchResult;
    }

    /**
     * Return total count of search and filtered result
     *
     * @return int
     */
    public function getTotalCount() : int
    {
        return $this->totalCount;
    }

    /**
     * Retrieve an array in the format of GraphQL-readable type containing category data.
     *
     * @return array
     */
    public function getCategoriesSearchResult() : array
    {
        return $this->categoriesSearchResult;
    }
}

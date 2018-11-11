<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Product\Reviews;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Class SearchResult
 * @package Magento_ReviewGraphQl
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
    private $reviewsSearchResult;

    /**
     * @param int $totalCount
     * @param array $reviewsSearchResult
     */
    public function __construct(int $totalCount, array $reviewsSearchResult)
    {
        $this->totalCount = $totalCount;
        $this->reviewsSearchResult = $reviewsSearchResult;
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
     * Retrieve an array in the format of GraphQL-readable type containing reviews data.
     *
     * @return array
     */
    public function getReviewsSearchResult() : array
    {
        return $this->reviewsSearchResult;
    }
}

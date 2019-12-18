<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Product\Reviews\Query;

use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\ReviewGraphQl\Model\Resolver\Product\Reviews\SearchResult;
use Magento\ReviewGraphQl\Model\Resolver\Product\Reviews\SearchResultFactory;
use Magento\ReviewGraphQl\Model\Resolver\Product\Reviews\DataProvider;

/**
 * Class Filter
 * @package Magento_ReviewGraphQl
 */
class Filter
{
    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var DataProvider
     */
    private $reviewsDataProvider;

    /**
     * Filter constructor
     *
     * @param SearchResultFactory $searchResultFactory
     * @param DataProvider $reviewsDataProvider
     */
    public function __construct(
        SearchResultFactory $searchResultFactory,
        DataProvider $reviewsDataProvider
    ) {
        $this->searchResultFactory = $searchResultFactory;
        $this->reviewsDataProvider = $reviewsDataProvider;
    }

    /**
     * Filter review data based on given search criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResult
     */
    public function getResult(
        SearchCriteriaInterface $searchCriteria
    ): SearchResult
    {
        $reviews = $this->reviewsDataProvider->getList($searchCriteria);
        return $this->searchResultFactory->create($reviews->getTotalCount(), $reviews->getItems());
    }
}

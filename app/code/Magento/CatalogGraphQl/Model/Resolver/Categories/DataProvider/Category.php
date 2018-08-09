<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Categories\DataProvider;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Api\Data\CategorySearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\CatalogGraphQl\Model\Resolver\Categories\DataProvider\Category\CollectionProcessorInterface;

/**
 * Category field data provider, used for GraphQL resolver processing.
 */
class Category
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CategorySearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @param CollectionFactory $collectionFactory
     * @param CategorySearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        CategorySearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * Gets list of category data with full data set. Adds eav attributes to result set from passed in array
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param string[] $attributes
     * @param bool $isSearch
     * @param bool $isChildSearch
     * @return SearchResultsInterface
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria,
        array $attributes = [],
        bool $isSearch = false,
        bool $isChildSearch = false
    ): SearchResultsInterface {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($collection, $searchCriteria, $attributes);

        $collection->load();

        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        return $searchResult;
    }
}

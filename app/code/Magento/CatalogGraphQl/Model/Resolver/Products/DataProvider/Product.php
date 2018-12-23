<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\Data\ProductSearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionProcessorInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionBuilderInterface;

/**
 * Product field data provider, used for GraphQL resolver processing.
 */
class Product
{
    /**
     * @var ProductSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionBuilderInterface
     */
    private $collectionBuilder;

    /**
     * Product constructor.
     * @param ProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionBuilderInterface $collectionBuilder
     */
    public function __construct(
        ProductSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionBuilderInterface $collectionBuilder
    ) {
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionBuilder = $collectionBuilder;
    }

    /**
     * Gets list of product data with full data set. Adds eav attributes to result set from passed in array
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
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->collectionBuilder->build($searchCriteria, $attributes, $isSearch, $isChildSearch);
        $collection->load();

        // Methods that perform extra fetches post-load
        if (in_array('media_gallery_entries', $attributes)) {
            $collection->addMediaGalleryData();
        }
        if (in_array('options', $attributes)) {
            $collection->addOptionsToResult();
        }

        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        return $searchResult;
    }
}

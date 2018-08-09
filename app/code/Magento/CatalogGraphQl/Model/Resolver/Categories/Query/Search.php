<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Categories\Query;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\CatalogGraphQl\Model\Resolver\Categories\SearchCriteria\Helper\Filter as FilterHelper;
use Magento\CatalogGraphQl\Model\Resolver\Categories\SearchResult;
use Magento\CatalogGraphQl\Model\Resolver\Categories\SearchResultFactory;
use Magento\Search\Api\SearchInterface;

/**
 * Full text search for catalog using given search criteria.
 */
class Search
{
    /**
     * @var SearchInterface
     */
    private $search;

    /**
     * @var FilterHelper
     */
    private $filterHelper;

    /**
     * @var Filter
     */
    private $filterQuery;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @param SearchInterface $search
     * @param FilterHelper $filterHelper
     * @param Filter $filterQuery
     * @param SearchResultFactory $searchResultFactory
     */
    public function __construct(
        SearchInterface $search,
        FilterHelper $filterHelper,
        Filter $filterQuery,
        SearchResultFactory $searchResultFactory,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool
    ) {
        $this->search = $search;
        $this->filterHelper = $filterHelper;
        $this->filterQuery = $filterQuery;
        $this->searchResultFactory = $searchResultFactory;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Return results of full text catalog search of given term, and will return filtered results if filter is specified
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResult
     */
    public function getResult(SearchCriteriaInterface $searchCriteria, ResolveInfo $info) : SearchResult
    {
        $idField = $this->metadataPool->getMetadata(
            \Magento\Catalog\Api\Data\CategoryInterface::class
        )->getIdentifierField();
        $realPageSize = $searchCriteria->getPageSize();
        $realCurrentPage = $searchCriteria->getCurrentPage();
        // Current page must be set to 0 and page size to max for search to grab all ID's as temporary workaround
        $searchCriteria->setPageSize(PHP_INT_MAX);
        $searchCriteria->setCurrentPage(0);
        $itemsResults = $this->search->search($searchCriteria);

        $ids = [];
        $searchIds = [];
        foreach ($itemsResults->getItems() as $item) {
            $ids[$item->getId()] = null;
            $searchIds[] = $item->getId();
        }

        $filter = $this->filterHelper->generate($idField, 'in', $searchIds);
        $searchCriteria = $this->filterHelper->remove($searchCriteria, 'search_term');
        $searchCriteria = $this->filterHelper->add($searchCriteria, $filter);
        $searchResult = $this->filterQuery->getResult($searchCriteria, $info, true);

        $searchCriteria->setPageSize($realPageSize);
        $searchCriteria->setCurrentPage($realCurrentPage);
        $paginatedCategories = $this->paginateList($searchResult, $searchCriteria);

        $categories = [];
        if (!isset($searchCriteria->getSortOrders()[0])) {
            foreach ($paginatedCategories as $category) {
                if (in_array($category[$idField], $searchIds)) {
                    $ids[$category[$idField]] = $category;
                }
            }
            $categories = array_filter($ids);
        } else {
            foreach ($paginatedCategories as $category) {
                $categoryId = isset($category['entity_id']) ? $category['entity_id'] : $category[$idField];
                if (in_array($categoryId, $searchIds)) {
                    $categories[] = $category;
                }
            }
        }

        return $this->searchResultFactory->create($searchResult->getTotalCount(), $categories);
    }

    /**
     * Paginate an array of Ids that get pulled back in search based off search criteria and total count.
     *
     * @param SearchResult $searchResult
     * @param SearchCriteriaInterface $searchCriteria
     * @return int[]
     */
    private function paginateList(SearchResult $searchResult, SearchCriteriaInterface $searchCriteria) : array
    {
        $length = $searchCriteria->getPageSize();
        // Search starts pages from 0
        $offset = $length * ($searchCriteria->getCurrentPage() - 1);

        if ($searchCriteria->getPageSize()) {
            $maxPages = ceil($searchResult->getTotalCount() / $searchCriteria->getPageSize()) - 1;
        } else {
            $maxPages = 0;
        }

        if ($searchCriteria->getCurrentPage() > $maxPages && $searchResult->getTotalCount() > 0) {
            $offset = (int)$maxPages;
        }
        return array_slice($searchResult->getCategoriesSearchResult(), $offset, $length);
    }
}

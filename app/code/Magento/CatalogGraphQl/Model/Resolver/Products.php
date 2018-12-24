<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver;

use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResult;
use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\Query\QueryInterface;

/**
 * Products field resolver, used for GraphQL request processing.
 */
class Products implements ResolverInterface
{
    /**
     * @var Builder
     */
    private $searchCriteriaBuilder;

    /**
     * @var QueryInterface[]
     */
    private $queries;

    /**
     * @param Builder $searchCriteriaBuilder
     * @param QueryInterface[] $queries
     */
    public function __construct(
        Builder $searchCriteriaBuilder,
        array $queries = []
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->queries = $queries;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $searchCriteria = $this->searchCriteriaBuilder->build($field->getName(), $args);
        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);

        $layerType = null;
        foreach (array_reverse($this->queries) as $key => $query) {
            if (isset($args[$key])) {
                $layerType = $query->getLayerType();
                $searchResult = $this->getSearchResult($query, $searchCriteria, $info, $args);
                break;
            }
        }

        if (is_null($layerType)) {
            throw new GraphQlInputException(
                __("%1 input argument is required.", implode(' or ', array_keys($this->queries)))
            );
        }

        //possible division by 0
        if ($searchCriteria->getPageSize()) {
            $maxPages = ceil($searchResult->getTotalCount() / $searchCriteria->getPageSize());
        } else {
            $maxPages = 0;
        }

        $currentPage = $searchCriteria->getCurrentPage();
        if ($searchCriteria->getCurrentPage() > $maxPages && $searchResult->getTotalCount() > 0) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the %2 page(s) available.',
                    [$currentPage, $maxPages]
                )
            );
        }

        $data = [
            'total_count' => $searchResult->getTotalCount(),
            'items' => $searchResult->getProductsSearchResult(),
            'page_info' => [
                'page_size' => $searchCriteria->getPageSize(),
                'current_page' => $currentPage,
                'total_pages' => $maxPages
            ],
            'layer_type' => $layerType
        ];

        return $data;
    }

    /**
     * Get search result.
     *
     * @param QueryInterface $query
     * @param SearchCriteriaInterface $searchCriteria
     * @param ResolveInfo $info
     * @param array $args
     *
     * @return \Magento\CatalogGraphQl\Model\Resolver\Products\SearchResult
     * @throws GraphQlInputException
     */
    private function getSearchResult(
        QueryInterface $query,
        SearchCriteriaInterface $searchCriteria,
        ResolveInfo $info,
        array $args
    ): SearchResult {
        try {
            $searchResult = $query->getResult($searchCriteria, $info, $args);
        } catch (InputException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        return $searchResult;
    }
}

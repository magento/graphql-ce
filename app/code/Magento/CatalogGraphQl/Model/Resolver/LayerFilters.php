<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\CatalogGraphQl\Model\Resolver\Layer\DataProvider\Filters as FiltersDataProvider;
use Magento\CatalogGraphQl\Model\Resolver\Layer\ProductCollection\FilterByCollectionProcessorInterface;
use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product\CollectionBuilderInterface;

/**
 * Layered navigation filters resolver, used for GraphQL request processing.
 */
class LayerFilters implements ResolverInterface
{
    /**
     * @var Layer\DataProvider\Filters
     */
    private $filtersDataProvider;

    /**
     * @var FilterByCollectionProcessorInterface
     */
    private $filterLayerByCollectionProcessor;

    /**
     * @var CollectionBuilderInterface
     */
    private $collectionBuilder;

    /**
     * LayerFilters constructor.
     * @param FiltersDataProvider $filtersDataProvider
     * @param FilterByCollectionProcessorInterface $filterLayerByCollectionProcessor
     * @param CollectionBuilderInterface $collectionBuilder
     */
    public function __construct(
        FiltersDataProvider $filtersDataProvider,
        FilterByCollectionProcessorInterface $filterLayerByCollectionProcessor,
        CollectionBuilderInterface $collectionBuilder
    ) {
        $this->filtersDataProvider = $filtersDataProvider;
        $this->filterLayerByCollectionProcessor = $filterLayerByCollectionProcessor;
        $this->collectionBuilder = $collectionBuilder;
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
        if (!isset($value['layer_type'])) {
            return null;
        }

        $searchCriteria = isset($value['search_criteria']) ? $value['search_criteria'] : null;
        if ($searchCriteria) {
            $isSearch = ($value['layer_type'] === \Magento\Catalog\Model\Layer\Resolver::CATALOG_LAYER_SEARCH);
            $collection = $this->collectionBuilder->build($searchCriteria, [], $isSearch);
            $this->filterLayerByCollectionProcessor->apply($collection);
        }

        return $this->filtersDataProvider->getData($value['layer_type']);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Layer;

use Magento\Catalog\Model\Layer\FilterListFactory;
use Magento\Catalog\Model\Layer\Resolver;

/**
 * Layer types filters provider.
 */
class FiltersProvider
{
    /**
     * @var Resolver
     */
    private $layerResolver;

    /**
     * @var FilterableAttributesListFactory
     */
    private $filterableAttributesListFactory;

    /**
     * @var FilterListFactory
     */
    private $filterListFactory;

    /**
     * @param Resolver $layerResolver
     * @param FilterableAttributesListFactory $filterableAttributesListFactory
     * @param FilterListFactory $filterListFactory
     */
    public function __construct(
        Resolver $layerResolver,
        FilterableAttributesListFactory $filterableAttributesListFactory,
        FilterListFactory $filterListFactory
    ) {
        $this->layerResolver = $layerResolver;
        $this->filterableAttributesListFactory = $filterableAttributesListFactory;
        $this->filterListFactory = $filterListFactory;
    }

    /**
     * Get layer type filters.
     *
     * @param string $layerType
     * @param null|int $categoryIdFilter
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFilters(string $layerType, ?int $categoryIdFilter = null) : array
    {
        $filterableAttributesList = $this->filterableAttributesListFactory->create(
            $layerType
        );
        $filterList = $this->filterListFactory->create(
            [
                'filterableAttributes' => $filterableAttributesList
            ]
        );

        if ($categoryIdFilter !== null) {
            $this->layerResolver->get()->setCurrentCategory($categoryIdFilter);
        }

        $filters = $filterList->getFilters($this->layerResolver->get());

        $filtersWithItems = [];
        foreach ($filters as $filter) {
            if ($filter->getItemsCount()) {
                $filtersWithItems[] = $filter;
            }
        }

        return $filtersWithItems;
    }
}

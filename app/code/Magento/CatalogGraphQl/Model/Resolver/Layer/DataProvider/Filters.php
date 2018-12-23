<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Layer\DataProvider;

use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\CatalogGraphQl\Model\Resolver\Layer\FiltersProvider;

/**
 * Layered navigation filters data provider.
 */
class Filters
{
    /**
     * @var FiltersProvider
     */
    private $filtersProvider;

    /**
     * Filters constructor.
     * @param FiltersProvider $filtersProvider
     */
    public function __construct(
        FiltersProvider $filtersProvider
    ) {
        $this->filtersProvider = $filtersProvider;
    }

    /**
     * Get layered navigation filters data
     *
     * @param string $layerType
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getData(string $layerType) : array
    {
        $filtersData = [];
        /** @var AbstractFilter $filter */
        foreach ($this->filtersProvider->getFilters($layerType) as $filter) {
            if ($filter->getItemsCount() > 1) {
                $filterGroup = [
                    'name' => (string)$filter->getName(),
                    'filter_items_count' => $filter->getItemsCount(),
                    'request_var' => $filter->getRequestVar(),
                ];
                /** @var \Magento\Catalog\Model\Layer\Filter\Item $filterItem */
                foreach ($filter->getItems() as $filterItem) {
                    if ($filterItem->getCount()) {
                        $filterGroup['filter_items'][] = [
                            'label' => (string)$filterItem->getLabel(),
                            'value_string' => $filterItem->getValueString(),
                            'items_count' => $filterItem->getCount(),
                        ];
                    }
                }

                if (isset($filterGroup['filter_items']) && count($filterGroup['filter_items'])) {
                    $filtersData[] = $filterGroup;
                }
            }
        }
        return $filtersData;
    }
}

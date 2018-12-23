<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Layer\ProductCollection;

use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

/**
 * Class FilterBySelectProcessor
 * @package Magento\GraphQl\Model\Resolver\Products\DataProvider\Layer\ProductCollection
 */
class FilterByCollectionProcessor implements FilterByCollectionProcessorInterface
{
    /** @const string */
    const FILTER_SELECT_ALIAS = 'filter_select';

    /**
     * @var LayerResolver
     */
    private $layerResolver;

    /**
     * FilterBySelectProcessor constructor
     *
     * @param LayerResolver $layerResolver
     */
    public function __construct(LayerResolver $layerResolver)
    {
        $this->layerResolver = $layerResolver;
    }

    /**
     * @inheritdoc
     */
    public function apply(Collection $filterByCollection): Collection
    {
        $filterSelect = clone $filterByCollection->getSelect();
        $filterSelect->reset(\Zend_Db_Select::LIMIT_COUNT)
            ->reset(\Zend_Db_Select::LIMIT_OFFSET);

        $layerProductCollection = $this->layerResolver->get()->getProductCollection();
        $linkField = $filterByCollection->getEntity()->getLinkField();
        $mainTableAlias = Collection::MAIN_TABLE_ALIAS;
        $filterSelectAlias = static::FILTER_SELECT_ALIAS;

        $layerProductCollection->getSelect()->joinInner(
            [$filterSelectAlias => $filterSelect],
            "{$mainTableAlias}.{$linkField} = {$filterSelectAlias}.{$linkField}",
            []
        );

        return $layerProductCollection;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Layer\ProductCollection;

use Magento\Catalog\Model\ResourceModel\Product\Collection;

/**
 * Interface FilterBySelectProcessorInterface
 * @package Magento\GraphQl\Model\Resolver\Products\DataProvider\Layer\ProductCollection
 */
interface FilterByCollectionProcessorInterface
{
    /**
     * @param Collection $filterByCollection
     * @return Collection
     */
    public function apply(Collection $filterByCollection): Collection;
}

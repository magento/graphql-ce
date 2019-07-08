<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magento\CatalogGraphQl\Model\Product\Option;

/**
 * Class DateTypePool
 *
 * Pool of Date Types
 */
class DateTypePool
{
    /**
     * @var array
     */
    protected $dataTypes;

    /**
     * @param array $dataTypes
     */
    public function __construct(array $dataTypes = [])
    {
        $this->dataTypes = $dataTypes;
    }

    /**
     * Retrieve Date Types
     *
     * @return array
     */
    public function getDataTypes()
    {
        return $this->dataTypes;
    }
}

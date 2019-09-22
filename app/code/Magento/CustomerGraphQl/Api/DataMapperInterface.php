<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGraphQl\Api;

/**
 * Data mapper interface
 */
interface DataMapperInterface
{
    /**
     * Execute the data mapping
     * @param array $data
     * @return array
     */
    public function execute(array $data): array;
}

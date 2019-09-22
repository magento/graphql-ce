<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGraphQl\Model\Customer\DataMapper;

use Magento\CustomerGraphQl\Api\DataMapperInterface;

/**
 * Data mapper pool, will perform the data mapping on customer response using the data mappers
 */
class FieldsDataMapper implements DataMapperInterface
{
    /**
     * Fields mapping configuration
     * @var array
     */
    private $fieldsMap;

    /**
     * Fields mapper initialization
     * @param array $fieldsMap
     */
    public function __construct(array $fieldsMap = [])
    {
        $this->fieldsMap = $fieldsMap;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $data): array
    {
        foreach ($this->fieldsMap as $fieldName => $valuesMapConfig) {
            if (isset($data[$fieldName])) {
                $data[$valuesMapConfig['fieldName'] ?? $fieldName] = $valuesMapConfig['map'][$data[$fieldName]]
                    ?? ($valuesMapConfig['defaultValue'] ?? $data[$fieldName]);
            }
        }

        return $data;
    }
}

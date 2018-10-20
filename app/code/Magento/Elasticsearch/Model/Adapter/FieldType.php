<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Elasticsearch\Model\Adapter;

use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;

/**
 * Class FieldType
 * @api
 * @since 100.1.0
 */
class FieldType
{
    /**#@+
     * Text flags for Elasticsearch field types
     */
    const ES_DATA_TYPE_STRING = 'string';
    const ES_DATA_TYPE_FLOAT = 'float';
    const ES_DATA_TYPE_INT = 'integer';
    const ES_DATA_TYPE_DATE = 'date';

    /** @deprecated */
    const ES_DATA_TYPE_ARRAY = 'array';
    /**#@-*/

    /**
     * Get field type by attribute
     *
     * @param AbstractAttribute $attribute
     * @return string
     * @since 100.1.0
     */
    public function getFieldType($attribute)
    {
        $backendType = $attribute->getBackendType();
        $frontendInput = $attribute->getFrontendInput();

        if ($backendType === 'timestamp') {
            $fieldType = self::ES_DATA_TYPE_DATE;
        } elseif ((in_array($backendType, ['int', 'smallint'], true)
            || (in_array($frontendInput, ['select', 'boolean'], true) && $backendType !== 'varchar'))
            && !$attribute->getIsUserDefined()
        ) {
            $fieldType = self::ES_DATA_TYPE_INT;
        } elseif ($backendType === 'decimal') {
            $fieldType = self::ES_DATA_TYPE_FLOAT;
        } else {
            $fieldType = self::ES_DATA_TYPE_STRING;
        }

        return $fieldType;
    }
}

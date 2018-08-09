<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Categories\FilterArgument;

use Magento\Framework\GraphQl\Config\Element\InterfaceType;
use Magento\Framework\GraphQl\Config\Element\Type;
use Magento\Framework\GraphQl\ConfigInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\FieldEntityAttributesInterface;

/**
 * Retrieves attributes for a field for the ast converter
 */
class CategoryEntityAttributesForAst implements FieldEntityAttributesInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAttributes() : array
    {
        $categoryTreeSchema = $this->config->getConfigElement('CategoryTree');
        if (!$categoryTreeSchema instanceof Type) {
            throw new \LogicException(__("CategoryTree type not defined in schema."));
        }

        $fields = [];
        foreach ($categoryTreeSchema->getInterfaces() as $interface) {
            /** @var InterfaceType $configElement */
            $configElement = $this->config->getConfigElement($interface['interface']);

            foreach ($configElement->getFields() as $field) {
                $fields[$field->getName()] = 'String';
            }
        }

        return array_keys($fields);
    }
}

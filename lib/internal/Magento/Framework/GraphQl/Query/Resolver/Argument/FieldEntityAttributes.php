<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\GraphQl\Query\Resolver\Argument;

use Magento\Framework\GraphQl\Config\Element\InterfaceType;
use Magento\Framework\GraphQl\Config\Element\Type;
use Magento\Framework\GraphQl\ConfigInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\FieldEntityAttributesInterface;

/**
 * Default implementation to retrieves attributes for a field for the ast converter.
 */
class FieldEntityAttributes implements FieldEntityAttributesInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var array
     */
    private $additionalAttributes;

    /**
     * @var string
     */
    private $configElementName;

    /**
     * @param ConfigInterface $config
     * @param string $configElementName
     * @param array $additionalAttributes
     */
    public function __construct(
        ConfigInterface $config,
        string $configElementName,
        array $additionalAttributes = []
    )
    {
        $this->config = $config;
        $this->configElementName = $configElementName;
        $this->additionalAttributes = $additionalAttributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAttributes(): array
    {
        $productTypeSchema = $this->config->getConfigElement($this->configElementName);
        if (!$productTypeSchema instanceof Type) {
            throw new \LogicException(__('%1 type not defined in schema.', $this->configElementName));
        }

        $fields = [];
        foreach ($productTypeSchema->getInterfaces() as $interface) {
            /** @var InterfaceType $configElement */
            $configElement = $this->config->getConfigElement($interface['interface']);

            foreach ($configElement->getFields() as $field) {
                $fields[$field->getName()] = '';
            }
        }

        foreach ($this->additionalAttributes as $attribute) {
            $fields[$attribute] = '';
        }

        return array_keys($fields);
    }
}

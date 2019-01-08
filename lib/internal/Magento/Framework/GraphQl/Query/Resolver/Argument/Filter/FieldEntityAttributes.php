<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\GraphQl\Query\Resolver\Argument\Filter;

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
    public function __construct(ConfigInterface $config, string $configElementName, array $additionalAttributes = [])
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
        $typeSchema = $this->config->getConfigElement($this->configElementName);
        if (!$typeSchema instanceof Type) {
            throw new \LogicException(__('%1 type not defined in schema.', $this->configElementName));
        }

        $configElements = $this->getSchemaConfigElements($typeSchema);

        $fields = [];
        foreach ($configElements as $configElement) {
            foreach ($configElement->getFields() as $field) {
                $fields[$field->getName()] = 'String';
            }
        }

        foreach ($this->additionalAttributes as $attribute) {
            $fields[$attribute] = 'String';
        }

        return array_keys($fields);
    }

    /**
     * Get the config elements for an entity.
     * @param Type $type
     * @return Type[]
     */
    private function getSchemaConfigElements(Type $type): array
    {
        $configElements = [];

        if ($type->getInterfaces() === null) {
            $configElements[] = $type;
            return $configElements;
        }

        foreach ($type->getInterfaces() as $interface) {
            $configElements[] = $this->config->getConfigElement($interface['interface']);
        }

        return $configElements;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var \Magento\Framework\ObjectManagerInterface $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$installer = Bootstrap::getObjectManager()->create(\Magento\Catalog\Setup\CategorySetup::class);
$data = [
    'attribute_code' => 'color_swatch',
    'entity_type_id' => $installer->getEntityTypeId('catalog_product'),
    'is_global' => 1,
    'is_user_defined' => 1,
    'frontend_input' => 'select',
    'is_unique' => 0,
    'is_required' => 0,
    'is_searchable' => 1,
    'is_visible_in_advanced_search' => 1,
    'is_comparable' => 1,
    'is_filterable' => 1,
    'is_filterable_in_search' => 1,
    'is_used_for_promo_rules' => 0,
    'is_html_allowed_on_front' => 1,
    'is_visible_on_front' => 1,
    'used_in_product_listing' => 1,
    'used_for_sort_by' => 1,
    'frontend_label' => ['Color Swatch', 'Store Label'],
    'backend_type' => 'int',
    'use_product_image_for_swatch' => 0,
    'update_product_preview_image' => 0,
];
$optionsPerAttribute = 3;

$data['swatch_input_type'] = 'visual';
$data['swatchvisual']['value'] = array_reduce(
    range(1, $optionsPerAttribute),
    function ($values, $index) use ($optionsPerAttribute) {
        $values['option_' . $index] = '#'
            . str_repeat(
                dechex(255 * $index / $optionsPerAttribute),
                3
            );
        return $values;
    },
    []
);
$data['optionvisual']['value'] = array_reduce(
    range(1, $optionsPerAttribute),
    function ($values, $index) use ($optionsPerAttribute) {
        $values['option_' . $index] = ['option ' . $index];
        return $values;
    },
    []
);

$data['options']['option'] = array_reduce(
    range(1, $optionsPerAttribute),
    function ($values, $index) use ($optionsPerAttribute) {
        $values[] = [
            'label' => 'option ' . $index,
            'value' => 'option_' . $index,
        ];
        return $values;
    },
    []
);

$options = [];
foreach ($data['options']['option'] as $optionData) {
    $options[] = $objectManager->get(AttributeOptionInterface::class)
        ->setLabel($optionData['label'])
        ->setValue($optionData['value']);
}

$attribute = $objectManager->create(
    \Magento\Catalog\Api\Data\ProductAttributeInterface::class,
    ['data' => $data]
);
$attribute->setOptions($options);
$attribute->save();

/* Assign attribute to attribute set */
$installer->addAttributeToGroup('catalog_product', 'Default', 'General', $attribute->getId());

/** @var \Magento\Eav\Model\Config $eavConfig */
$eavConfig = Bootstrap::getObjectManager()->get(\Magento\Eav\Model\Config::class);
$eavConfig->clear();

$attribute = $eavConfig->getAttribute('catalog_product', 'color_swatch');
$options = $attribute->getOptions();

// workaround for saved attribute
$attribute->setDefaultValue($options[1]->getValue());

$attribute->save();
$eavConfig->clear();
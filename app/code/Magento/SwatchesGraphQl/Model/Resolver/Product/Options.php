<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SwatchesGraphQl\Model\Resolver\Product;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * Class Options
 * @package Magento\SwatchesGraphQl\Model\Resolver\Product
 */
class Options implements ResolverInterface
{

    /**
     * @var \Magento\Swatches\Helper\Data
     */
    private $helper;

    /**
     * Options constructor.
     * @param \Magento\Swatches\Helper\Data $helper
     */
    public function __construct(
        \Magento\Swatches\Helper\Data $helper
    )
    {
        $this->helper = $helper;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return \Magento\Framework\GraphQl\Query\Resolver\Value|mixed
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        $swatches = $this->helper->getSwatchesByOptionsId([$value['value_index']]);
        $swatchData = [
            'value' => $swatches[$value['value_index']]['value'],
            'type' => $swatches[$value['value_index']]['type']
        ];

        return $swatchData;
    }
}

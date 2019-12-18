<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Customer\Product\Reviews\FilterArgument;

use Magento\Framework\GraphQl\Config\Element\InterfaceType;
use Magento\Framework\GraphQl\Config\Element\Type;
use Magento\Framework\GraphQl\ConfigInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\FieldEntityAttributesInterface;

/**
 * Class ReviewEntityAttributesForAst
 * @package Magento_ReviewGraphQl
 */
class ReviewEntityAttributesForAst implements FieldEntityAttributesInterface
{
    /**
     * @var array
     */
    private $additionalAttributes = ['review_id'];

    /**
     * @param ConfigInterface $config
     * @param array $additionalAttributes
     */
    public function __construct(
        ConfigInterface $config,
        array $additionalAttributes = []
    ) {
        $this->additionalAttributes = array_merge($this->additionalAttributes, $additionalAttributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityAttributes() : array
    {
        $fields = [];
        foreach ($this->additionalAttributes as $attribute) {
            $fields[$attribute] = 'String';
        }

        return array_keys($fields);
    }
}

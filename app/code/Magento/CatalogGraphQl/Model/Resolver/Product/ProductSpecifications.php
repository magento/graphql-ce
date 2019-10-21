<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * Class ProductSpecifications
 *
 * @package Magento\CatalogGraphQl\Model\Resolver\Product
 */
class ProductSpecifications implements ResolverInterface
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * ProductSpecifications constructor.
     *
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var Product $product */
        $product = $value['model'];

        return $this->getAdditionalData($product);
    }

    /**
     * Retrieve the Product Specifications based on the AttributeSet of the product
     *
     * @param $product
     * @return array
     */
    public function getAdditionalData(
        $product
    ): array {
        $data = [];
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {
            if ($this->isVisibleOnFrontend($attribute)) {
                if (!$product->getData($attribute->getCode())) {
                    $this->getRawAttributeDataForProduct($product, $attribute);
                }

                $value = $attribute->getFrontend()->getValue($product);

                if ($value instanceof Phrase) {
                    $value = (string)$value;
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = $this->priceCurrency->convertAndFormat($value);
                }

                if (is_string($value) && strlen(trim($value))) {
                    $data[] = [
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code' => $attribute->getAttributeCode(),
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * Retrieve the raw attribute data for the product
     *
     * @param $product
     * @param $attribute
     */
    protected function getRawAttributeDataForProduct(
        $product,
        $attribute
    ) {
        $value = $product->getResource()->getAttributeRawValue(
            $product->getId(),
            $attribute->getAttributeCode(),
            false
        );
        if (!empty($value)) {
            $product->setData($attribute->getAttributeCode(), $value);
        }
    }

    /**
     * Check if the attribute is set to is_visible_on_front
     *
     * @param \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute
     * @return bool
     */
    protected function isVisibleOnFrontend(
        \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute
    ):bool {
        return $attribute->getIsVisibleOnFront();
    }
}

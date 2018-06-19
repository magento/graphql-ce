<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Model\Stock\ItemFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolve data for product stock count
 */
class IsInStock implements ResolverInterface
{
    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * @var Item
     */
    private $item;

    /**
     * @param ValueFactory $valueFactory
     * @param Item $item
     */
    public function __construct(
        ValueFactory $valueFactory,
        ItemFactory $itemFactory
    ) {
        $this->valueFactory = $valueFactory;
        $this->itemFactory = $itemFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): Value {
        if (!isset($value['model'])) {
            $result = function () {
                return null;
            };
            return $this->valueFactory->create($result);
        }

        $stockItem = $this->itemFactory->create();

        /* @var $product Product */
        $product = $value['model'];
        $isInStock = $stockItem->load($product->getId(), 'product_id')->getIsInStock();
        $result = function () use ($isInStock) {
            return $isInStock;
        };

        return $this->valueFactory->create($result);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Product;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolve data for product stock count
 */
class OnlyXLeftInStock implements ResolverInterface
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
        Item $item
    ) {
        $this->valueFactory = $valueFactory;
        $this->item = $item;
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

        /* @var $product Product */
        $product = $value['model'];
        $qty = $this->item->load($product->getId(), 'product_id')->getQty();
        $result = function () use ($qty) {
            return $qty;
        };

        return $this->valueFactory->create($result);
    }
}

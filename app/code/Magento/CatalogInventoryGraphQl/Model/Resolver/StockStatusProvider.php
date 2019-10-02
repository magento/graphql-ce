<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryGraphQl\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * @inheritdoc
 */
class StockStatusProvider implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!array_key_exists('model', $value) || !$value['model'] instanceof ProductInterface) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /* @var $product ProductInterface */
        $product = $value['model'];

        return $product->isSaleable() ? 'IN_STOCK' : 'OUT_OF_STOCK';
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductCompareGraphQl\Model\Resolver;

use Magento\Catalog\Model\Product\Compare\ItemsFromListProvider;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * CompareProducts field resolver.
 */
class GetCompareListItems implements ResolverInterface
{
    /**
     * @var ItemsFromListProvider
     */
    private $itemsFromListProvider;

    /**
     * @param ItemsFromListProvider $itemsFromListProvider
     */
    public function __construct(ItemsFromListProvider $itemsFromListProvider)
    {
        $this->itemsFromListProvider = $itemsFromListProvider;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $items = $this->itemsFromListProvider->get($context->getUserId(), $context->getData('hashed_id'));

        return $items;
    }
}

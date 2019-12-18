<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductCompareGraphQl\Model\Resolver;

use Magento\Catalog\Model\Product\Compare\CreateList;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Create new compare list.
 */
class CreateCompareList implements ResolverInterface
{
    /**
     * @var CreateList
     */
    private $createList;

    /**
     * @param CreateList $createList
     */
    public function __construct(
        CreateList $createList
    ) {
        $this->createList = $createList;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $customerId = $context->getUserId();

        $compareList = $this->createList->execute($customerId);

        return ['hashed_id' => $compareList->getHashedId()];
    }
}

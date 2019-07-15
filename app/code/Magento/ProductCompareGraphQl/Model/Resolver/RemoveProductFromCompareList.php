<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductCompareGraphQl\Model\Resolver;

use Magento\Catalog\Model\Product\Compare\RemoveFromList;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolver for Remove Product(products) from Compare List.
 */
class RemoveProductFromCompareList implements ResolverInterface
{
    /**
     * @var RemoveFromList
     */
    private $removeFromList;

    /**
     * @param RemoveFromList $removeFromList
     */
    public function __construct(RemoveFromList $removeFromList)
    {
        $this->removeFromList = $removeFromList;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['input']) || !is_array($args['input']) || empty($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        if (!isset($args['hashed_id']) || !is_string($args['hashed_id'])) {
            throw new GraphQlInputException(__('"hashed_id" value should be specified'));
        }

        $context->setData('hashed_id', $args['hashed_id']);
        $result = ['result' => false, "compareProducts" => []];

        if (!empty($args['input']['ids']) && is_array($args['input']['ids'])) {
            $customerId = $context->getUserId();

            foreach ($args['input']['ids'] as $id) {
                $this->removeFromList->execute($customerId, $args['hashed_id'], (int)$id);
            }

            $result = ['result' => true, "compareProductsList" => []];
        }

        return $result;
    }
}

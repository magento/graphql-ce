<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\FieldResolverInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\QueryInterface;
use Magento\Framework\GraphQl\FieldConfigInterface;

/**
 * @inheritdoc
 */
class Cart implements FieldResolverInterface
{
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @param GetCartForUser $getCartForUser
     */
    public function __construct(
        GetCartForUser $getCartForUser
    ) {
        $this->getCartForUser = $getCartForUser;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        ContextInterface $resolverContext,
        QueryInterface $query,
        FieldConfigInterface $fieldConfig,
        ?array $parentResolvedValue
    ) {
        if ($query->getArgument('cart_id') === null) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }
        $maskedCartId = $query->getArgument('cart_id');

        $currentUserId = $resolverContext->getUserId();
        $cart = $this->getCartForUser->execute($maskedCartId, $currentUserId);

        return [
            'model' => $cart,
        ];
    }
}

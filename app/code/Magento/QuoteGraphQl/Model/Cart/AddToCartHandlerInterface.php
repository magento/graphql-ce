<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Cart;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Quote\Model\Quote;

/**
 */
interface AddToCartHandlerInterface
{
    /**
     * @param CartInterface $cart
     * @param ContextInterface $context
     * @return void
     * @throws GraphQlInputException
     */
    public function execute(Quote $cart, array $cartItemData): void;
}

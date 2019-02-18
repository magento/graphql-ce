<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Cart;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Quote\Api\Data\CartInterface;

/**
 */
interface AddToCartHandlerInterface
{
    /**
     * @param CartInterface $cart
     * @param array $cartItemData
     * @return void
     * @throws GraphQlInputException
     */
    public function execute(CartInterface $cart, array $cartItemData): void;
}

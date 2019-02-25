<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Cart;

use Magento\Quote\Model\Quote;

/**
 * All product types should extend this interface
 * TODO: comment should be updated
 */
interface AddToCartHandlerInterface
{
    /**
     * @param Quote $cart
     * @param array $cartItemData
     * @return void
     */
    public function execute(Quote $cart, array $cartItemData): void;
}

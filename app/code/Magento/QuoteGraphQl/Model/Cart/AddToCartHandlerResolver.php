<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Cart;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * @inheritdoc
 */
class AddToCartHandlerResolver
{
    /**
     * @var array
     */
    private $supportedHandlers = [];

    /**
     * @param array $supportedHandlers
     */
    public function __construct(array $supportedHandlers = [])
    {
        $this->supportedHandlers = $supportedHandlers;
    }

    /**
     * @inheritdoc
     */
    public function getAddToCartHandler(array $data) : AddToCartHandlerInterface
    {
        if (isset($data[0]['data']['grouped_products'])) {
            return $this->supportedHandlers['grouped'];
        }
        return $this->supportedHandlers['simple'];
    }
}

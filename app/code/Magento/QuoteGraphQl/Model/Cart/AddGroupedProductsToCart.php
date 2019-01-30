<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Cart;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Message\AbstractMessage;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

/**
 * Add products to cart
 */
class AddGroupedProductsToCart implements AddToCartHandlerInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var AddGroupedProductToCart
     */
    private $addProductToCart;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param AddGroupedProductToCart $addProductToCart
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        AddGroupedProductToCart $addProductToCart
    ) {
        $this->cartRepository = $cartRepository;
        $this->addProductToCart = $addProductToCart;
    }

    /**
     * Add products to cart
     *
     * @param Quote $cart
     * @param array $cartItems
     * @throws GraphQlInputException
     */
    public function execute(Quote $cart, array $cartItems): void
    {
        foreach ($cartItems as $cartItemData) {
            $this->addProductToCart->execute($cart, $cartItemData);
        }

        if ($cart->getData('has_error')) {
            throw new GraphQlInputException(
                __('Shopping cart error: %message', ['message' => $this->getCartErrors($cart)])
            );
        }

        $this->cartRepository->save($cart);
    }

    /**
     * Collecting cart errors
     *
     * @param Quote $cart
     * @return string
     */
    private function getCartErrors(Quote $cart): string
    {
        $errorMessages = [];

        /** @var AbstractMessage $error */
        foreach ($cart->getErrors() as $error) {
            $errorMessages[] = $error->getText();
        }

        return implode(PHP_EOL, $errorMessages);
    }
}

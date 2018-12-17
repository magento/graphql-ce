<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultishippingGraphQl\Model\SetShippingAddressesOnCart\MultiShipping;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Shipping address to shipping items mapper
 */
class ShippingItemsMapper
{
    /**
     * Converts shipping address input array into shipping items information array
     * Array structure:
     * array(
     *      $cartItemId => array(
     *          'qty'       => $qty,
     *          'address'   => $customerAddressId
     *      )
     * )
     *
     * @param array $shippingAddress
     * @param CartInterface $cart
     * @return array
     * @throws GraphQlInputException
     */
    public function map(array $shippingAddress, CartInterface $cart): array
    {
        $shippingItemsInformation = [];
        foreach ($shippingAddress['cart_items'] as $cartItem) {
            $this->validateItem($cart, $cartItem['cart_item_id']);
            $shippingItemsInformation[] = [
                $cartItem['cart_item_id'] => [
                    'qty' => $cartItem['quantity'],
                    'address' => $shippingAddress['customer_address_id']
                ]
            ];
        }

        return $shippingItemsInformation;
    }

    /**
     *  Check whether Cart Item exist
     *
     * @param CartInterface $cart
     * @param int $cartItemId
     * @throws GraphQlInputException
     */
    private function validateItem(CartInterface $cart, int $cartItemId): void
    {
        if (!$cart->getItemById($cartItemId)) {
            throw new GraphQlInputException(__('No such item added to cart with id %1', $cartItemId));
        }
    }
}

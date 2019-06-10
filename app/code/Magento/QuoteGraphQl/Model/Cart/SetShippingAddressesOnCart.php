<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Cart;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\QuoteGraphQl\Model\Cart\Address\SaveQuoteAddressToAddressBook;

/**
 * Set single shipping address for a specified shopping cart
 */
class SetShippingAddressesOnCart implements SetShippingAddressesOnCartInterface
{
    /**
     * @var QuoteAddressFactory
     */
    private $quoteAddressFactory;

    /**
     * @var AssignShippingAddressToCart
     */
    private $assignShippingAddressToCart;

    /**
     * @var SaveQuoteAddressToAddressBook
     */
    private $saveQuoteAddressToAddressBook;

    /**
     * @param QuoteAddressFactory $quoteAddressFactory
     * @param AssignShippingAddressToCart $assignShippingAddressToCart
     * @param SaveQuoteAddressToAddressBook $saveQuoteAddressToAddressBook
     */
    public function __construct(
        QuoteAddressFactory $quoteAddressFactory,
        AssignShippingAddressToCart $assignShippingAddressToCart,
        SaveQuoteAddressToAddressBook $saveQuoteAddressToAddressBook
    ) {
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->assignShippingAddressToCart = $assignShippingAddressToCart;
        $this->saveQuoteAddressToAddressBook = $saveQuoteAddressToAddressBook;
    }

    /**
     * @inheritdoc
     */
    public function execute(CartInterface $cart, array $shippingAddressesInput): void
    {
        if (count($shippingAddressesInput) > 1) {
            throw new GraphQlInputException(
                __('You cannot specify multiple shipping addresses.')
            );
        }
        $shippingAddressInput = current($shippingAddressesInput);
        $customerAddressId = $shippingAddressInput['customer_address_id'] ?? null;
        $addressInput = $shippingAddressInput['address'] ?? null;

        if (null === $customerAddressId && null === $addressInput) {
            throw new GraphQlInputException(
                __('The shipping address must contain either "customer_address_id" or "address".')
            );
        }

        if ($customerAddressId && $addressInput) {
            throw new GraphQlInputException(
                __('The shipping address cannot contain "customer_address_id" and "address" at the same time.')
            );
        }

        $customerId = (int)$cart->getCustomerId();
        if (null === $customerAddressId) {
            $shippingAddress = $this->quoteAddressFactory->createBasedOnInputData($addressInput);
        } else {
            $shippingAddress = $this->quoteAddressFactory->createBasedOnCustomerAddress(
                (int)$customerAddressId,
                $customerId
            );
        }
        $this->assignShippingAddressToCart->execute($cart, $shippingAddress);

        if (!empty($addressInput) && !empty($addressInput['save_in_address_book']) && 0 !== $customerId) {
            $this->saveQuoteAddressToAddressBook->execute($shippingAddress, $customerId);
        }
    }
}

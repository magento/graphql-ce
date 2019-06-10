<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Cart;

use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\QuoteGraphQl\Model\Cart\Address\SaveQuoteAddressToAddressBook;

/**
 * Set billing address for a specified shopping cart
 */
class SetBillingAddressOnCart
{
    /**
     * @var QuoteAddressFactory
     */
    private $quoteAddressFactory;

    /**
     * @var AssignBillingAddressToCart
     */
    private $assignBillingAddressToCart;

    /**
     * @var SaveQuoteAddressToAddressBook
     */
    private $saveQuoteAddressToAddressBook;

    /**
     * @param QuoteAddressFactory $quoteAddressFactory
     * @param AssignBillingAddressToCart $assignBillingAddressToCart
     * @param SaveQuoteAddressToAddressBook $saveQuoteAddressToAddressBook
     */
    public function __construct(
        QuoteAddressFactory $quoteAddressFactory,
        AssignBillingAddressToCart $assignBillingAddressToCart,
        SaveQuoteAddressToAddressBook $saveQuoteAddressToAddressBook
    ) {
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->assignBillingAddressToCart = $assignBillingAddressToCart;
        $this->saveQuoteAddressToAddressBook = $saveQuoteAddressToAddressBook;
    }

    /**
     * Set billing address for a specified shopping cart
     *
     * @param CartInterface $cart
     * @param array $billingAddressInput
     * @return void
     * @throws GraphQlInputException
     * @throws GraphQlAuthenticationException
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    public function execute(CartInterface $cart, array $billingAddressInput): void
    {
        $customerAddressId = $billingAddressInput['customer_address_id'] ?? null;
        $addressInput = $billingAddressInput['address'] ?? null;
        $useForShipping = isset($billingAddressInput['use_for_shipping'])
            ? (bool)$billingAddressInput['use_for_shipping'] : false;

        if (null === $customerAddressId && null === $addressInput) {
            throw new GraphQlInputException(
                __('The billing address must contain either "customer_address_id" or "address".')
            );
        }

        if ($customerAddressId && $addressInput) {
            throw new GraphQlInputException(
                __('The billing address cannot contain "customer_address_id" and "address" at the same time.')
            );
        }

        $addresses = $cart->getAllShippingAddresses();
        if ($useForShipping && count($addresses) > 1) {
            throw new GraphQlInputException(
                __('Using the "use_for_shipping" option with multishipping is not possible.')
            );
        }

        $customerId = (int)$cart->getCustomerId();
        // customer, customer_address_id = null, save_in_address_book = true - positive
        // customer, customer_address_id = Int, save_in_address_book = true - negative
        // guest, customer_address_id = Int - negative
        // guest, customer_address_id = null, save_in_address_book = true - negative

        if (null === $customerAddressId) {
            $billingAddress = $this->quoteAddressFactory->createBasedOnInputData($addressInput);

            if (!empty($addressInput['save_in_address_book'])) {
                $this->saveQuoteAddressToAddressBook->execute($billingAddress, $customerId);
            }
        } else {
            $billingAddress = $this->quoteAddressFactory->createBasedOnCustomerAddress(
                (int)$customerAddressId,
                $customerId
            );
        }
        $this->assignBillingAddressToCart->execute($cart, $billingAddress, $useForShipping);
    }
}

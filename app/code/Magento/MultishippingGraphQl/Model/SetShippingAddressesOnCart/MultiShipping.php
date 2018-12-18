<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultishippingGraphQl\Model\SetShippingAddressesOnCart;

use Magento\CustomerGraphQl\Model\Customer\CheckCustomerAccount;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Multishipping\Helper\Data as MultishippingHelper;
use Magento\MultishippingGraphQl\Model\Builder\MultiShipping as MultiShippingBuilder;
use Magento\MultishippingGraphQl\Model\SetShippingAddressesOnCart\MultiShipping\ShippingItemsMapper;
use Magento\Quote\Api\Data\CartInterface;
use Magento\QuoteGraphQl\Model\Cart\SetShippingAddressesOnCartInterface;
use Magento\QuoteGraphQl\Model\Cart\SetShippingAddressOnCart;

/**
 * Multishipping address assigning flow
 */
class MultiShipping implements SetShippingAddressesOnCartInterface
{
    /**
     * @var ShippingItemsMapper
     */
    private $shippingItemsMapper;

    /**
     * @var SetShippingAddressOnCart
     */
    private $setShippingAddressOnCart;

    /**
     * @var MultishippingHelper
     */
    private $multishippingHelper;

    /**
     * @var MultiShippingBuilder
     */
    private $multiShippingBuilder;

    /**
     * @var CheckCustomerAccount
     */
    private $checkCustomerAccount;

    /**
     * @param ShippingItemsMapper $shippingItemsMapper
     * @param SetShippingAddressOnCart $setShippingAddressOnCart
     * @param MultishippingHelper $multishippingHelper
     * @param MultiShippingBuilder $multiShippingBuilder
     * @param CheckCustomerAccount $checkCustomerAccount
     */
    public function __construct(
        ShippingItemsMapper $shippingItemsMapper,
        SetShippingAddressOnCart $setShippingAddressOnCart,
        MultishippingHelper $multishippingHelper,
        MultiShippingBuilder $multiShippingBuilder,
        CheckCustomerAccount $checkCustomerAccount
    ) {
        $this->shippingItemsMapper = $shippingItemsMapper;
        $this->setShippingAddressOnCart = $setShippingAddressOnCart;
        $this->multishippingHelper = $multishippingHelper;
        $this->multiShippingBuilder = $multiShippingBuilder;
        $this->checkCustomerAccount = $checkCustomerAccount;
    }

    /**
     * @inheritdoc
     */
    public function execute(ContextInterface $context, CartInterface $cart, array $shippingAddresses): void
    {
        if (count($shippingAddresses) === 1 || !$this->multishippingHelper->isMultishippingCheckoutAvailable()) {
            $this->setShippingAddressOnCart->execute($context, $cart, $shippingAddresses);
            return;
        }
        $this->checkCustomerAccount->execute($context->getUserId(), $context->getUserType());

        $shippingItemsInformation = [];
        foreach ($shippingAddresses as $shippingAddress) {
            $customerAddressId = $shippingAddress['customer_address_id'] ?? null;
            $cartItems = $shippingAddress['cart_items'] ?? null;
            if (!$customerAddressId) {
                throw new GraphQlInputException(__('Parameter "customer_address_id" is required for multishipping'));
            }
            if (!$cartItems) {
                throw new GraphQlInputException(__('Parameter "cart_items" is required for multishipping'));
            }

            $shippingItemsInformation = array_merge(
                $shippingItemsInformation,
                $this->shippingItemsMapper->map($shippingAddress, $cart)
            );
        }

        $multiShippingModel = $this->multiShippingBuilder->get($context, $cart);
        try {
            $multiShippingModel->setShippingItemsInformation($shippingItemsInformation);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultishippingGraphQl\Model\SetShippingAddressesOnCart;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
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
    private $shippingItemsInformationMapper;

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
     * @param ShippingItemsMapper $shippingItemsInformationMapper
     * @param SetShippingAddressOnCart $setShippingAddressOnCart
     * @param MultishippingHelper $multishippingHelper
     * @param MultiShippingBuilder $multiShippingBuilder
     */
    public function __construct(
        ShippingItemsMapper $shippingItemsInformationMapper,
        SetShippingAddressOnCart $setShippingAddressOnCart,
        MultishippingHelper $multishippingHelper,
        MultiShippingBuilder $multiShippingBuilder
    ) {
        $this->shippingItemsInformationMapper = $shippingItemsInformationMapper;
        $this->setShippingAddressOnCart = $setShippingAddressOnCart;
        $this->multishippingHelper = $multishippingHelper;
        $this->multiShippingBuilder = $multiShippingBuilder;
    }

    /**
     * @inheritdoc
     */
    public function execute(ContextInterface $context, CartInterface $cart, array $shippingAddresses): void
    {
        $multiShippingModel = $this->multiShippingBuilder->get($context, $cart);
        if (count($shippingAddresses) === 1 || !$this->multishippingHelper->isMultishippingCheckoutAvailable()) {
            $this->setShippingAddressOnCart->execute($context, $cart, $shippingAddresses);
            return;
        }
        if ((!$context->getUserId()) || $context->getUserType() == UserContextInterface::USER_TYPE_GUEST) {
            throw new GraphQlAuthorizationException(
                __(
                    'Multishipping allowed only for authorized customers.'
                )
            );
        }

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
                $this->shippingItemsInformationMapper->map($shippingAddress)
            );
        }

        $multiShippingModel->setShippingItemsInformation($shippingItemsInformation);
    }
}

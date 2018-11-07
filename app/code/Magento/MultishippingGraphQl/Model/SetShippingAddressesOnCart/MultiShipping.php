<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MultishippingGraphQl\Model\SetShippingAddressesOnCart;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Multishipping\Helper\Data as MultishippingHelper;
use Magento\Multishipping\Model\Checkout\Type\Multishipping as MultishippingModel;
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
     * @var MultishippingModel
     */
    private $multishippingModel;

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
     * @var CustomerResource
     */
    private $customerResource;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @param MultishippingModel $multishippingModel
     * @param ShippingItemsMapper $shippingItemsInformationMapper
     * @param SetShippingAddressOnCart $setShippingAddressOnCart
     * @param MultishippingHelper $multishippingHelper
     * @param CustomerResource $customerResource
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        MultishippingModel $multishippingModel,
        ShippingItemsMapper $shippingItemsInformationMapper,
        SetShippingAddressOnCart $setShippingAddressOnCart,
        MultishippingHelper $multishippingHelper,
        CustomerResource $customerResource,
        CustomerFactory $customerFactory
    ) {
        $this->multishippingModel = $multishippingModel;
        $this->shippingItemsInformationMapper = $shippingItemsInformationMapper;
        $this->setShippingAddressOnCart = $setShippingAddressOnCart;
        $this->multishippingHelper = $multishippingHelper;
        $this->customerResource = $customerResource;
        $this->customerFactory = $customerFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(ContextInterface $context, CartInterface $cart, array $shippingAddresses): void
    {
        $this->initModel($context, $cart);
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

        $this->multishippingModel->setShippingItemsInformation($shippingItemsInformation);
    }

    /**
     * Initialize Multishipping checkout model
     *
     * @param ContextInterface $context
     * @param CartInterface $cart
     */
    private function initModel(ContextInterface $context, CartInterface $cart): void
    {
        $customerSession = $this->multishippingModel->getCustomerSession();
        $customer = $this->customerFactory->create();
        $this->customerResource->load(
            $customer,
            $context->getUserId()
        );
        $customerData = $customer->getDataModel();
        $customerSession->setCustomer($customer);
        $customerSession->setCustomerDataObject($customerData);
        $this->multishippingModel->getCheckoutSession()->replaceQuote($cart);
    }
}

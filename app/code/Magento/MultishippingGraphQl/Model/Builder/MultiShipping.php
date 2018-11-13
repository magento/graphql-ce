<?php
/**
 * @author Atwix Team
 * @copyright Copyright (c) 2018 Atwix (https://www.atwix.com/)
 */
declare(strict_types=1);

namespace Magento\MultishippingGraphQl\Model\Builder;

use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Multishipping\Model\Checkout\Type\Multishipping as MultishippingModel;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Multishipping address assigning flow
 */
class MultiShipping
{
    /**
     * @var MultishippingModel
     */
    private $multishippingModel;

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
     * @param CustomerResource $customerResource
     * @param CustomerFactory $customerFactory
     */
    public function __construct(
        MultishippingModel $multishippingModel,
        CustomerResource $customerResource,
        CustomerFactory $customerFactory
    ) {
        $this->multishippingModel = $multishippingModel;
        $this->customerResource = $customerResource;
        $this->customerFactory = $customerFactory;
    }

    /**
     * Initialize Multishipping checkout model
     *
     * @param ContextInterface $context
     * @param CartInterface $cart
     * @return MultishippingModel
     */
    public function get(ContextInterface $context, CartInterface $cart): MultishippingModel
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
        $this->multishippingModel->getQuote()->setIsMultiShipping(1);

        return $this->multishippingModel;
    }
}

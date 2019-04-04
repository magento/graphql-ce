<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NewsletterGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Newsletter\Model\SubscriberFactory;

class ManageSubscription implements ResolverInterface
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @param CustomerRepository $customerRepository
     * @param SubscriberFactory $subscriberFactory
     */
    public function __construct(CustomerRepository $customerRepository, SubscriberFactory $subscriberFactory)
    {
        $this->customerRepository = $customerRepository;
        $this->subscriberFactory = $subscriberFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $customerId = $context->getUserId();

        /* Guest checking */
        if (!$customerId && 0 === $customerId) {
            throw new GraphQlAuthorizationException(__('The current user cannot perform operations on newsletter subscription'));
        }

        $customer = $this->customerRepository->getById($customerId);

        $isSubscribed = (boolean)$field['is_subscribed'];
        $isSubscribedState = $customer->getExtensionAttributes()
            ->getIsSubscribed();

        if ($isSubscribed !== $isSubscribedState) {
            if ($isSubscribed) {
                $this->subscriberFactory->create()->subscribeCustomerById($customerId);
            } else {
                $this->subscriberFactory->create()->unsubscribeCustomerById($customerId);
            }
        }

        return true;
    }
}

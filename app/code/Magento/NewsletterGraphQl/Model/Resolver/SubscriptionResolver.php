<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NewsletterGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Authorization\Model\UserContextInterface;

/**
 * Customer Newsletter Subscription field resolver, used for GraphQL request processing.
 */
class SubscriptionResolver implements ResolverInterface
{
    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @param ValueFactory $valueFactory
     * @param Subscriber $subscriber
     * @param UserContextInterface $userContext
     */
    public function __construct(
        ValueFactory $valueFactory,
        Subscriber $subscriber,
        UserContextInterface $userContext
    ) {
        $this->valueFactory = $valueFactory;
        $this->subscriber = $subscriber;
        $this->userContext = $userContext;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) : Value {

        $customerId = $this->userContext->getUserId();

        $subscriberInfo = ['is_subscribed' => false];
        if ($customerId) {
            $subscriber = $this->subscriber->loadByCustomerId($customerId);
            $subscriberInfo = ['is_subscribed' => $subscriber->isSubscribed()];
        }
        $result = function () use ($subscriberInfo) {
            return $subscriberInfo;
        };

        return $this->valueFactory->create($result);
    }

}

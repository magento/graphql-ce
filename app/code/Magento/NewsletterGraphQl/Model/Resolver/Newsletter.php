<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\NewsletterGraphQl\Model\Resolver;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Newsletter\Model\Subscriber;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * @inheritdoc
 */
class Newsletter implements ResolverInterface
{
    /**
     * @var Subscriber
     */
    private $subscriber;

    /**
     * @param \Magento\Newsletter\Model\Subscriber $subscriber
     */
    public function __construct(
        Subscriber $subscriber
    ) {
        $this->subscriber = $subscriber;
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
        /** @var ContextInterface $context */
        try {
            if ($context->getUserType() === UserContextInterface::USER_TYPE_GUEST) {
                $subscriber = $this->subscriber->loadByEmail($args['subscriber_email']);
            } else {
                $subscriber = $this->subscriber->loadByCustomerId($context->getUserId());
            }
            return [
                'subscriber_id'           => $subscriber->getSubscriberId(),
                'store_id'                => $subscriber->getStoreId(),
                'change_status_at'        => $subscriber->getChangeStatusAt(),
                'customer_id'             => $subscriber->getCustomerId(),
                'subscriber_email'        => $subscriber->getSubscriberEmail(),
                'subscriber_status'       => $subscriber->getSubscriberStatus() === Subscriber::STATUS_SUBSCRIBED,
                'subscriber_confirm_code' => $subscriber->getSubscriberConfirmCode(),
            ];
        } catch (\Exception $exception) {
            throw new GraphQlNoSuchEntityException(__('Subscriber "%1" does not exist.', [$args['subscriber_email']]));
        }
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\NewsletterGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Newsletter\Model\SubscriberFactory;

/**
 * @inheritdoc
 */
class UnsubscribeSubscriber implements ResolverInterface
{
    /**
     * Subscriber factory
     *
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * ConfirmSubscriber constructor.
     * @param SubscriberFactory $subscriberFactory
     */
    public function __construct(SubscriberFactory $subscriberFactory)
    {
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
        if (!isset($args['input']) || !is_array($args['input']) || empty($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        if (!isset($args['input']['id'])) {
            throw new GraphQlInputException(__('Specify the "id" value.'));
        }

        if (!isset($args['input']['code'])) {
            throw new GraphQlInputException(__('Specify the "code" value.'));
        }

        $id = (int)$args['input']['id'];
        $code = (string)$args['input']['code'];

        $this->subscriberFactory->create()->load($id)->setCheckCode($code)->unsubscribe();
        return true;
    }
}

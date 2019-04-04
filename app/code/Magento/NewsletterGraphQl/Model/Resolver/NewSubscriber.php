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
use Magento\Framework\Validator\EmailAddress as EmailValidator;

/**
 * @inheritdoc
 */
class NewSubscriber implements ResolverInterface
{
    /**
     * Subscriber factory
     *
     * @var SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var EmailValidator
     */
    private $emailValidator;

    /**
     * ConfirmSubscriber constructor.
     * @param SubscriberFactory $subscriberFactory
     */
    public function __construct(
        SubscriberFactory $subscriberFactory,
        EmailValidator $emailValidator
    ) {
        $this->subscriberFactory = $subscriberFactory;
        $this->emailValidator = $emailValidator;
    }

    /**
     * Validates the format of the email address
     *
     * @param string $email
     * @return void
     */
    protected function validateEmailFormat($email)
    {
        if (!$this->emailValidator->isValid($email)) {
            throw new GraphQlInputException(__('Please enter a valid email address.'));
        }
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($args['email'])) {
            throw new GraphQlInputException(__('Specify the "email" value.'));
        }

        $email = (string)$args['email'];
        $this->validateEmailFormat($email);

        $status = (int)$this->subscriberFactory->create()->subscribe($email);

        if ($status == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
            return true;
        }

        return false;
    }
}

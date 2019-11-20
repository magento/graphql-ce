<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGraphQl\Model\Resolver;

use Magento\CustomerGraphQl\Model\Customer\CheckCustomerPassword;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\CustomerGraphQl\Model\Customer\SaveCustomer;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Change customer email resolver
 */
class UpdateCustomerEmail implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var CheckCustomerPassword
     */
    private $checkCustomerPassword;

    /**
     * @var ExtractCustomerData
     */
    private $extractCustomerData;

    /**
     * @var SaveCustomer
     */
    private $saveCustomer;

    /**
     * @param GetCustomer $getCustomer
     * @param CheckCustomerPassword $checkCustomerPassword
     * @param ExtractCustomerData $extractCustomerData
     * @param SaveCustomer $saveCustomer
     */
    public function __construct(
        GetCustomer $getCustomer,
        CheckCustomerPassword $checkCustomerPassword,
        ExtractCustomerData $extractCustomerData,
        SaveCustomer $saveCustomer
    ) {
        $this->getCustomer = $getCustomer;
        $this->checkCustomerPassword = $checkCustomerPassword;
        $this->extractCustomerData = $extractCustomerData;
        $this->saveCustomer = $saveCustomer;
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
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        if ('' === trim($args['email'])) {
            throw new GraphQlInputException(__('Specify the "email" value.'));
        }

        if ('' === trim($args['password'])) {
            throw new GraphQlInputException(__('Specify the "password" value.'));
        }

        $customer = $this->getCustomer->execute($context);
        $this->checkCustomerPassword->execute($args['password'], (int)$customer->getId());
        $customer->setEmail($args['email']);
        $this->saveCustomer->execute($customer);
        $data = $this->extractCustomerData->execute($customer);
        return ['customer' => $data];
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGraphQl\Model\Resolver\V2;

use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\CustomerGraphQl\Model\Customer\UpdateCustomerData;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\CustomerGraphQl\Model\Customer\ExtractCustomerData;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Update customer data resolver v2
 */
class UpdateCustomer implements ResolverInterface
{
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * @var UpdateCustomerData
     */
    private $updateCustomerData;

    /**
     * @var ExtractCustomerData
     */
    private $extractCustomerData;

    /**
     * @param GetCustomer $getCustomer
     * @param UpdateCustomerData $updateCustomerData
     * @param ExtractCustomerData $extractCustomerData
     */
    public function __construct(
        GetCustomer $getCustomer,
        UpdateCustomerData $updateCustomerData,
        ExtractCustomerData $extractCustomerData
    )
    {
        $this->getCustomer = $getCustomer;
        $this->updateCustomerData = $updateCustomerData;
        $this->extractCustomerData = $extractCustomerData;
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
        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }
        if (isset($args['input']['date_of_birth'])) {
            $args['input']['dob'] = $args['input']['date_of_birth'];
        }
        $customer = $this->getCustomer->execute($context);
        $this->updateCustomerData->execute(
            $customer,
            $args['input'],
            $context->getExtensionAttributes()->getStore()
        );
        $data = $this->extractCustomerData->execute($customer);
        return ['customer' => $data];
    }
}

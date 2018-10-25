<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VaultGraphQl\Model\Resolver;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\VaultGraphQl\Model\Resolver\PaymentToken\PaymentTokenDataProvider;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Store Payment Method List, used for GraphQL request processing.
 */
class PaymentTokenList implements ResolverInterface
{
    /**
     * @var PaymentTokenDataProvider
     */
    private $paymentTokenDataProvider;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagementInterface;

    /**
     * @param PaymentTokenDataProvider $paymentTokenDataProvider
     * @param PaymentTokenManagementInterface $paymentTokenManagementInterface
     */
    public function __construct(
        PaymentTokenDataProvider $paymentTokenDataProvider,
        PaymentTokenManagementInterface $paymentTokenManagementInterface
    ) {
        $this->paymentTokenDataProvider = $paymentTokenDataProvider;
        $this->paymentTokenManagementInterface = $paymentTokenManagementInterface;
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
        /** @var \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context */
        if ((!$context->getUserId()) || $context->getUserType() == UserContextInterface::USER_TYPE_GUEST) {
            throw new GraphQlAuthorizationException(
                __(
                    'Current customer does not have access to the resource "%1"',
                    ['store_payment_token']
                )
            );
        }
        $customerId = $context->getUserId();
        return $this->paymentTokenDataProvider->processPaymentTokens(
            $this->paymentTokenManagementInterface->getVisibleAvailableTokens($customerId)
        );
    }
}
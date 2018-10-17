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
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

/**
 * Store Payment Method List, used for GraphQL request processing.
 */
class StorePaymentTokenDelete implements ResolverInterface
{
    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepositoryInterface;

    /**
     * @param PaymentTokenRepositoryInterface $paymentTokenRepositoryInterface
     */
    public function __construct(
        PaymentTokenRepositoryInterface $paymentTokenRepositoryInterface
    ) {
        $this->paymentTokenRepositoryInterface = $paymentTokenRepositoryInterface;
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
        return $this->deleteToken($customerId, $args['id']);
    }

    /**
     * Process delete request
     *
     * @param int $customerId
     * @param int $tokenId
     * @return bool
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    private function deleteToken($customerId, $tokenId)
    {
        /** @var \Magento\Vault\Api\Data\PaymentTokenInterface $token */
        $token = $this->paymentTokenRepositoryInterface->getById($tokenId);
        if (empty($token->getEntityId())) {
            throw new GraphQlNoSuchEntityException(
                __('Payment Token id %1 does not exist.', [$tokenId])
            );
        }
        if ($customerId != $token->getCustomerId()) {
            throw new GraphQlAuthorizationException(
                __('Current customer does not have permission to delete Payment Token id %1', [$tokenId])
            );
        }

        return $this->paymentTokenRepositoryInterface->delete($token);
    }
}
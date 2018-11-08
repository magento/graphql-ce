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
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Store Payment Delete, used for GraphQL request processing.
 */
class PaymentTokenDelete implements ResolverInterface
{
    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     */
    public function __construct(
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentTokenManagement = $paymentTokenManagement;
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
        if ((!$context->getUserId()) || $context->getUserType() == UserContextInterface::USER_TYPE_GUEST) {
            throw new GraphQlAuthorizationException(
                __(
                    'A guest customer cannot access resource "%1".',
                    ['store_payment_token']
                )
            );
        }
        $customerId = $context->getUserId();
        return $this->deleteToken($customerId, $args['public_hash']);
    }

    /**
     * Process delete request
     *
     * @param int $customerId
     * @param string $publicHash
     * @return bool
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     */
    private function deleteToken($customerId, $publicHash)
    {
        $token = $this->paymentTokenManagement->getByPublicHash($publicHash, $customerId);
        if (!$token) {
            throw new GraphQlNoSuchEntityException(
                __('Payment token public_hash %1 does not exist.', [$publicHash])
            );
        }
        return $this->paymentTokenRepository->delete($token);
    }
}
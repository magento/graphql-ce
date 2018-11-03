<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VaultGraphQl\Model\Resolver;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlAlreadyExistsException;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\VaultGraphQl\Model\Resolver\PaymentToken\PaymentTokenDataProvider;

/**
 * Store Payment Add, used for GraphQL request processing.
 */
class PaymentTokenAdd implements ResolverInterface
{
    /**
     * Payment token required attributes
     */
    const REQUIRED_ATTRIBUTES = [
        'public_hash',
        'payment_method_code',
        'type',
        'gateway_token'
    ];

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var PaymentTokenFactoryInterface
     */
    private $paymentTokenFactory;

    /**
     * @var PaymentTokenDataProvider
     */
    private $paymentTokenDataProvider;

    /**
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param PaymentTokenDataProvider $paymentTokenDataProvider
     */
    public function __construct(
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        PaymentTokenDataProvider $paymentTokenDataProvider
    )
    {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenDataProvider = $paymentTokenDataProvider;
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
    )
    {
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
        return $this->paymentTokenDataProvider->processPaymentToken(
            $this->processPaymentTokenAdd($customerId, $args['input'])
        );
    }

    /**
     * Check input data
     *
     * @param array $tokenInfo
     * @return bool|string
     */
    private function getInputError(array $tokenInfo)
    {
        foreach (self::REQUIRED_ATTRIBUTES as $attributeName) {
            if (!isset($tokenInfo[$attributeName]) || empty($tokenInfo[$attributeName])) {
                return $attributeName;
            }
        }
        return false;
    }

    /**
     * Process payment token add
     *
     * @param int $customerId
     * @param array $tokenInfo
     * @return PaymentTokenInterface
     * @throws GraphQlInputException
     * @throws GraphQlAlreadyExistsException
     */
    private function processPaymentTokenAdd($customerId, array $tokenInfo): PaymentTokenInterface
    {
        $errorInput = $this->getInputError($tokenInfo);
        if ($errorInput) {
            throw new GraphQlInputException(
                __('The required parameter %1 is missing.', [$errorInput])
            );
        }
        /** @var PaymentTokenInterface $token */
        $token = $this->paymentTokenDataProvider->fillPaymentToken(
            $this->paymentTokenFactory->create($tokenInfo['type']),
            $tokenInfo
        );
        $token->setCustomerId($customerId);
        try {
            $this->paymentTokenRepository->save($token);
            // Reload current token object from repository to get "created_at" updated
            return $this->paymentTokenRepository->getById($token->getEntityId());
        } catch (AlreadyExistsException $e) {
            throw new GraphQlAlreadyExistsException(__($e->getMessage()), $e);
        }
    }
}
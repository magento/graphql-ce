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
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlAlreadyExistsException;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\VaultGraphQl\Model\Resolver\PaymentToken\PaymentTokenDataProvider;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * Store Payment Method List, used for GraphQL request processing.
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
    private $paymentTokenRepositoryInterface;

    /**
     * @var PaymentTokenFactoryInterface
     */
    private $paymentTokenFactoryInterface;

    /**
     * @var PaymentTokenDataProvider
     */
    private $paymentTokenDataProvider;

    /**
     * @param PaymentTokenRepositoryInterface $paymentTokenRepositoryInterface
     * @param PaymentTokenFactoryInterface $paymentTokenInterface
     * @param PaymentTokenDataProvider $paymentTokenDataProvider
     */
    public function __construct(
        PaymentTokenRepositoryInterface $paymentTokenRepositoryInterface,
        PaymentTokenFactoryInterface $paymentTokenInterface,
        PaymentTokenDataProvider $paymentTokenDataProvider
    ) {
        $this->paymentTokenRepositoryInterface = $paymentTokenRepositoryInterface;
        $this->paymentTokenFactoryInterface = $paymentTokenInterface;
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
        foreach(self::REQUIRED_ATTRIBUTES as $attributeName){
            if(!isset($tokenInfo[$attributeName]) || empty($tokenInfo[$attributeName])){
                return $attributeName;
            }
        }
        return false;
    }

    /**
     * Process payment token add
     *
     * @param $customerId
     * @param array $tokenInfo
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     * @throws GraphQlInputException
     * @throws GraphQlAlreadyExistsException
     */
    private function processPaymentTokenAdd($customerId, array $tokenInfo)
    {
        $errorInput = $this->getInputError($tokenInfo);
        if ($errorInput) {
            throw new GraphQlInputException(
                __('Required parameter %1 is missing', [$errorInput])
            );
        }
        /** @var \Magento\Vault\Api\Data\PaymentTokenInterface $token */
        $token = $this->paymentTokenDataProvider->fillPaymentToken(
            $this->paymentTokenFactoryInterface->create($tokenInfo['type']),
            $tokenInfo
        );
        $token->setCustomerId($customerId);
        try {
            return $this->paymentTokenRepositoryInterface->save($token);
        }catch (AlreadyExistsException $e) {
            throw new GraphQlAlreadyExistsException(__($e->getMessage()), $e);
        }

    }
}
<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magento\VaultGraphQl\Model\Resolver\PaymentToken;

use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Framework\Serialize\SerializerInterface;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

/**
 * Payment Token field data provider, used for GraphQL request processing.
 */
class PaymentTokenDataProvider
{
    /**
     * @var ServiceOutputProcessor
     */
    private $serviceOutputProcessor;

    /**
     * @var SerializerInterface
     */
    private $jsonSerializer;

    /**
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param SerializerInterface $jsonSerializer
     */
    public function __construct(
        ServiceOutputProcessor $serviceOutputProcessor,
        SerializerInterface $jsonSerializer
    ) {
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface[] $paymentTokens
     * @return array
     */
    public function processPaymentTokens(array $paymentTokens) : array
    {
        $result = [];
        /** @var \Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken */
        foreach ($paymentTokens as $paymentToken) {
            $result[] = $this->processPaymentToken($paymentToken);
        }
        return $result;
    }

    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $paymentTokenObject
     * @return array
     */
    private function processPaymentToken(PaymentTokenInterface $paymentTokenObject) : array
    {
        $paymentToken = $this->serviceOutputProcessor->process(
            $paymentTokenObject,
            PaymentTokenRepositoryInterface::class,
            'getById'
        );
        $detail = $this->jsonSerializer->unserialize($paymentTokenObject->getTokenDetails());
        $paymentToken['expiration_date'] = $this->getExpirationDate($detail);
        $paymentToken['card_number'] = $this->getCardNumber($detail);
        $paymentToken['card_type'] = $this->getType($detail);
        return $paymentToken;
    }

    /**
     * @param array $paymentDetailArray
     * @return string
     */
    private function getExpirationDate(array $paymentDetailArray)
    {
        return $paymentDetailArray['expirationDate'];
    }

    /**
     * @param array $paymentDetailArray
     * @return string
     */
    private function getCardNumber(array $paymentDetailArray)
    {
        return $paymentDetailArray['maskedCC'];
    }

    /**
     * @param array $paymentDetailArray
     * @return string
     */
    private function getType(array $paymentDetailArray)
    {
        return $paymentDetailArray['type'];
    }

}

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

        $detailsAttributes = [];
        if (!empty($paymentTokenObject->getTokenDetails())) {
            $details = $this->jsonSerializer->unserialize($paymentTokenObject->getTokenDetails());
            foreach ($details as $key => $attribute) {
                $isArray = false;
                if (is_array($attribute)) {
                    $isArray = true;
                    foreach ($attribute as $attributeValue) {
                        if (is_array($attributeValue)) {
                            $detailsAttributes[] = [
                                'attribute_code' => $key,
                                'value' => $this->jsonSerializer->serialize($attribute)
                            ];
                            continue;
                        }
                        $detailsAttributes[] = ['attribute_code' => $key, 'value' => implode(',', $attribute)];
                        continue;
                    }
                }
                if ($isArray) {
                    continue;
                }
                $detailsAttributes[] = ['attribute_code' => $key, 'value' => $attribute];
            }
        }

        $paymentToken['details'] = $detailsAttributes;
        return $paymentToken;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Vault;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Framework\Serialize\SerializerInterface;

class PaymentTokenAddTest extends GraphQlAbstract
{
    /**
     * Verify add payment token with valid credentials
     *
     * @magentoApiDataFixture Magento/Vault/_files/customer.php
     */
    public function testAddPaymentTokenWithValidCredentials()
    {
        $userName = 'customer@example.com';
        $password = 'password';
        /** @var CustomerTokenServiceInterface $customerTokenService */
        $customerTokenService = ObjectManager::getInstance()->get(CustomerTokenServiceInterface::class);
        $customerToken = $customerTokenService->createCustomerAccessToken($userName, $password);
        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = ObjectManager::getInstance()->get(CustomerRepositoryInterface::class);
        $customer = $customerRepository->get($userName);
        $paymentTokenInfo = [
            'public_hash' => '123456789',
            'payment_method_code' => '987654321',
            'type' => 'card',
            'expires_at' => '2018-12-31 23:59:59',
            'gateway_token' => 'ABC123',
            'is_active' => true,
            'is_visible' => true,
            'details' => [
                ['attribute_code' => 'type', 'value' => 'VI'],
                ['attribute_code' => 'maskedCC', 'value' => '9876'],
                ['attribute_code' => 'expirationDate', 'value' => '12/2055'],

            ]
        ];
        $isActiveText = $paymentTokenInfo['is_active'] ? "true": "false";
        $isVisibleText = $paymentTokenInfo['is_visible'] ? "true": "false";
        $query
            = <<<MUTATION
mutation {
  paymentTokenAdd(input: {
    public_hash : "{$paymentTokenInfo['public_hash']}"
    payment_method_code: "{$paymentTokenInfo['payment_method_code']}"
    type: {$paymentTokenInfo['type']}
    expires_at: "{$paymentTokenInfo['expires_at']}"
    gateway_token: "{$paymentTokenInfo['gateway_token']}"
    details: [
          {
            attribute_code: "{$paymentTokenInfo['details'][0]['attribute_code']}",
            value: "{$paymentTokenInfo['details'][0]['value']}"
          },
          {
            attribute_code: "{$paymentTokenInfo['details'][1]['attribute_code']}",
            value: "{$paymentTokenInfo['details'][1]['value']}"
          },
          {
            attribute_code: "{$paymentTokenInfo['details'][2]['attribute_code']}",
            value: "{$paymentTokenInfo['details'][2]['value']}"
          }
        ],
    is_active: {$isActiveText}
    is_visible: {$isVisibleText}
  }) {
    entity_id
    customer_id
    public_hash
    payment_method_code
    type
    created_at
    expires_at
    gateway_token
    details {
      attribute_code
      value
    }
    is_active
    is_visible
  }
}
MUTATION;
        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('paymentTokenAdd', $response);
        $this->assertArrayHasKey('customer_id', $response['paymentTokenAdd']);
        $this->assertEquals($customer->getId(), $response['paymentTokenAdd']['customer_id']);
        $this->assertArrayHasKey('entity_id', $response['paymentTokenAdd']);
        $tokenId = $response['paymentTokenAdd']['entity_id'];
        /** @var PaymentTokenRepositoryInterface $tokenRepository */
        $tokenRepository = ObjectManager::getInstance()->get(PaymentTokenRepositoryInterface::class);
        /** @var PaymentTokenInterface $token */
        $token = $tokenRepository->getById($tokenId);
        $this->assertEquals($token->getEntityId(), $response['paymentTokenAdd']['entity_id']);
        $this->assertPaymentTokenFields($token, $response['paymentTokenAdd']);
        $this->assertPaymentTokenFields($token, $paymentTokenInfo);
    }

    /**
     * Verify add payment token without credentials
     */
    public function testAddPaymentTokenWithoutCredentials()
    {
        $query
            = <<<MUTATION
mutation {
  paymentTokenAdd(input: {
    public_hash : "112233445566"
    payment_method_code: "5544332211"
    type: card
    expires_at: "2020-01-01 00:00:00"
    gateway_token: "ABCDEF1234"
    details: [
          {
            attribute_code: "type",
            value: "MC"
          },
          {
            attribute_code: "maskedCC",
            value: "0000"
          },
          {
            attribute_code: "expirationDate",
            value: "01/2020"
          }
        ],
    is_active: true
    is_visible: true
  }) {
    entity_id
    customer_id
    public_hash
    payment_method_code
    type
    created_at
    expires_at
    gateway_token
    details {
      attribute_code
      value
    }
    is_active
    is_visible
  }
}
MUTATION;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors:' . ' ' .
            'Current customer does not have access to the resource "store_payment_token"');
        $this->graphQlQuery($query);
    }

    /**
     * Verify the fields for Customer address
     *
     * @param PaymentTokenInterface $paymentToken
     * @param array $actualResponse
     */
    private function assertPaymentTokenFields(PaymentTokenInterface $paymentToken, array $actualResponse)
    {
        $assertionMap = [
            ['response_field' => 'public_hash', 'expected_value' => $paymentToken->getPublicHash()],
            ['response_field' => 'payment_method_code', 'expected_value' => $paymentToken->getPaymentMethodCode()],
            ['response_field' => 'type', 'expected_value' => $paymentToken->getType()],
            ['response_field' => 'expires_at', 'expected_value' => $paymentToken->getExpiresAt()],
            ['response_field' => 'gateway_token', 'expected_value' => $paymentToken->getGatewayToken()],
            ['response_field' => 'is_active', 'expected_value' => $paymentToken->getIsActive()],
            ['response_field' => 'is_visible', 'expected_value' => $paymentToken->getIsVisible()],
        ];
        $this->assertResponseFields($actualResponse, $assertionMap);
        /** @var SerializerInterface $jsonSerializer */
        $jsonSerializer = ObjectManager::getInstance()->get(SerializerInterface::class);
        $paymentTokenDetailArray = $jsonSerializer->unserialize($paymentToken->getTokenDetails());
        foreach($actualResponse['details'] as $details) {
            $this->assertEquals($paymentTokenDetailArray[$details['attribute_code']], $details['value']);
        }
    }
}
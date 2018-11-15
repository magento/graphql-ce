<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Vault;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
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
            'details' => [
                ['code' => 'type', 'value' => 'VI'],
                ['code' => 'maskedCC', 'value' => '9876'],
                ['code' => 'expirationDate', 'value' => '12/2055'],

            ]
        ];
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
        code: "{$paymentTokenInfo['details'][0]['code']}",
        value: "{$paymentTokenInfo['details'][0]['value']}"
      },
      {
        code: "{$paymentTokenInfo['details'][1]['code']}",
        value: "{$paymentTokenInfo['details'][1]['value']}"
      },
      {
        code: "{$paymentTokenInfo['details'][2]['code']}",
        value: "{$paymentTokenInfo['details'][2]['value']}"
      }
    ]
  }) {
    public_hash
    payment_method_code
    type
    created_at
    expires_at
    details {
      code
      value
    }
  }
}
MUTATION;
        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('paymentTokenAdd', $response);
        $tokenPublicHash = $response['paymentTokenAdd']['public_hash'];
        /** @var PaymentTokenManagementInterface $tokenManager */
        $tokenManager = ObjectManager::getInstance()->get(PaymentTokenManagementInterface::class);
        /** @var PaymentTokenInterface $token */
        $token = $tokenManager->getByPublicHash($tokenPublicHash, $customer->getId());
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
        code: "type",
        value: "MC"
      },
      {
        code: "maskedCC",
        value: "0000"
      },
      {
        code: "expirationDate",
        value: "01/2020"
      }
    ]
  }) {
    public_hash
    payment_method_code
    type
    created_at
    expires_at
    details {
      code
      value
    }
  }
}
MUTATION;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors:' . ' ' .
            'A guest customer cannot access resource "store_payment_token".');
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
        ];
        $this->assertResponseFields($actualResponse, $assertionMap);
        /** @var SerializerInterface $jsonSerializer */
        $jsonSerializer = ObjectManager::getInstance()->get(SerializerInterface::class);
        $paymentTokenDetailArray = $jsonSerializer->unserialize($paymentToken->getTokenDetails());
        foreach ($actualResponse['details'] as $details) {
            $this->assertEquals($paymentTokenDetailArray[$details['code']], $details['value']);
        }
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Vault;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

class PaymentTokenListTest extends GraphQlAbstract
{
    /**
     * Verify store payment token with valid credentials
     *
     * @magentoApiDataFixture Magento/Vault/_files/payment_tokens.php
     */
    public function testPaymentTokenWithValidCredentials()
    {
        $query
            = <<<QUERY
{
  paymentTokenList {
    entity_id
    customer_id
    public_hash
    payment_method_code
    type
    created_at
    expires_at
    details {
      attribute_code
      value
    }
    is_active
    is_visible
  }
}
QUERY;

        $userName = 'customer@example.com';
        $password = 'password';
        /** @var CustomerTokenServiceInterface $customerTokenService */
        $customerTokenService = ObjectManager::getInstance()
            ->get(\Magento\Integration\Api\CustomerTokenServiceInterface::class);
        $customerToken = $customerTokenService->createCustomerAccessToken($userName, $password);
        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = ObjectManager::getInstance()->get(CustomerRepositoryInterface::class);
        $customer = $customerRepository->get($userName);

        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertTrue(is_array($response['paymentTokenList']), "paymentTokenList field must be of an array type.");
        $this->assertEquals($this->getPaymentTokenAmountFroCustomer($customer->getId()), count($response['paymentTokenList']));
        $list = $response['paymentTokenList'];
        $this->assertCustomerId($customer->getId(), $list);
        $this->assertIsActive($list);
        $this->assertIsDetailsArray($list);

        $this->assertEquals('1234', $list[0]['public_hash']);
        $this->assertEquals('12345', $list[1]['public_hash']);
        $this->assertEquals('23456', $list[2]['public_hash']);

        $this->assertEquals('first', $list[0]['payment_method_code']);
        $this->assertEquals('second', $list[1]['payment_method_code']);
        $this->assertEquals('third', $list[2]['payment_method_code']);

        $this->assertEquals('simple', $list[0]['type']);
        $this->assertEquals('simple', $list[1]['type']);
        $this->assertEquals('notsimple', $list[2]['type']);

        $this->assertEquals('2020-09-04 10:18:15', $list[0]['expires_at']);
        $this->assertEquals('2020-10-04 10:18:15', $list[1]['expires_at']);
        $this->assertEquals('2020-11-04 10:18:15', $list[2]['expires_at']);

        //TODO: add more checke
    }

    /**
     * Verify store payment token without credentials
     */
    public function testPaymentTokenWithoutCredentials()
    {
        $query
            = <<<QUERY
{
  paymentTokenList {
    entity_id
    customer_id
    public_hash
    payment_method_code
    type
    created_at
    expires_at
    details {
      attribute_code
      value
    }
    is_active
    is_visible
  }
}
QUERY;
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors:' . ' ' .
            'Current customer does not have access to the resource "store_payment_token"');
        $this->graphQlQuery($query);
    }

    /**
     * Verify Customer Id for all items in response
     *
     * @param int $customerId
     * @param array $response
     */
    private function assertCustomerId($customerId, array $response)
    {
        foreach ($response as $token) {
            $this->assertEquals($customerId, $token['customer_id']);
        }
    }

    /**
     * Verify Customer Id for all items in response
     *
     * @param array $response
     */
    private function assertIsActive(array $response)
    {
        foreach ($response as $token) {
            $this->assertTrue($token['is_active']);
        }
    }

    private function assertIsDetailsArray(array $response)
    {
        foreach ($response as $tokn) {
            $this->assertTrue(is_array($tokn['details']));
        }
    }

    /**
     * Get amount of customer payment token
     *
     * @param int $customerId
     * @return int
     */
    private function getPaymentTokenAmountFroCustomer($customerId)
    {
        /** @var \Magento\Vault\Model\PaymentTokenManagement $paymentTokenManagementInterface */
        $paymentTokenManagementInterface = ObjectManager::getInstance()
            ->get(PaymentTokenManagementInterface::class);
        return count($paymentTokenManagementInterface->getVisibleAvailableTokens($customerId));
    }
}


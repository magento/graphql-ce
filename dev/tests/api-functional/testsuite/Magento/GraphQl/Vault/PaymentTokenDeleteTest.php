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
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class PaymentTokenDeleteTest extends GraphQlAbstract
{
    /**
     * Verify delete payment token with valid credentials
     *
     * @magentoApiDataFixture Magento/Vault/_files/payment_tokens.php
     */
    public function testDeletePaymentTokenWithValidCredentials()
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
        /** @var \Magento\Vault\Api\PaymentTokenManagementInterface $tokenRepository */
        $tokenRepository = ObjectManager::getInstance()->get(PaymentTokenManagementInterface::class);
        /** @var \Magento\Vault\Api\Data\PaymentTokenInterface[] $tokenList */
        $tokenList = $tokenRepository->getListByCustomerId($customer->getId());
        /** @var \Magento\Vault\Api\Data\PaymentTokenInterface $token */
        $token = current($tokenList);
        $tokenId = $token->getEntityId();

        $query
            = <<<MUTATION
mutation {
  paymentTokenDelete(id: {$tokenId})
}
MUTATION;

        $response = $this->graphQlQuery($query, [], '', $headerMap);
        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('paymentTokenDelete', $response);
        $this->assertTrue($response['paymentTokenDelete']);
    }

    /**
     * Verify delete payment token with valid credentials
     */
    public function testDeletePaymentTokenWithoutCredentials()
    {
        $query
            = <<<MUTATION
mutation {
  paymentTokenDelete(id: 1)
}
MUTATION;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors:' . ' ' .
            'Current customer does not have access to the resource "store_payment_token"');
        $this->graphQlQuery($query);
    }
}
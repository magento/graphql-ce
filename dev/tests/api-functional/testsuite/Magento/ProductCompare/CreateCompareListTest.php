<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\ProductCompare;

use Magento\Catalog\Model\CompareList\CustomerIdByHashedIdProviderInterface;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class CreateCompareListTest extends GraphQlAbstract
{
    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testCreateCompareList()
    {
        $customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
        $customerIdByHashedIdProvider = Bootstrap::getObjectManager()->get(CustomerIdByHashedIdProviderInterface::class);

        $customerToken = $customerTokenService->createCustomerAccessToken('customer@example.com', 'password');
        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];

        $mutation
            = <<<MUTATION
mutation {
  createCompareList {
    hashed_id
  }
}
MUTATION;

        $response = $this->graphQlQuery($mutation, [], '', $headerMap);

        $this->assertArrayHasKey('createCompareList', $response);
        $this->assertInternalType('array', $response['createCompareList']);
        $this->assertInternalType('string', $response['createCompareList']['hashed_id']);

        $customerId = $customerIdByHashedIdProvider->get($response['createCompareList']['hashed_id']);
        $this->assertEquals(1, $customerId);
    }

    public function testCreateCompareListForGuest()
    {
        $customerIdByHashedIdProvider = Bootstrap::getObjectManager()->get(CustomerIdByHashedIdProviderInterface::class);

        $mutation
            = <<<MUTATION
mutation {
  createCompareList {
    hashed_id
  }
}
MUTATION;
        $response = $this->graphQlQuery($mutation);

        $this->assertArrayHasKey('createCompareList', $response);
        $this->assertInternalType('array', $response['createCompareList']);
        $this->assertInternalType('string', $response['createCompareList']['hashed_id']);

        $customerId = $customerIdByHashedIdProvider->get($response['createCompareList']['hashed_id']);
        $this->assertEquals(0, $customerId);
    }
}

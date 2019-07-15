<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\ProductCompare;

use Magento\Catalog\Model\Product\Compare\CreateList;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class AddProductToCompareListTest extends GraphQlAbstract
{
    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     */
    public function testAddProductToCompareList()
    {
        /** @var CreateList $createList */
        $createList = Bootstrap::getObjectManager()->get(CreateList::class);
        $customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);

        $customerToken = $customerTokenService->createCustomerAccessToken('customer@example.com', 'password');
        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];

        $compareList = $createList->execute(1);

        $mutation
            = <<<MUTATION
mutation {
  addProductToCompareList(
    input: {ids: [1, 21]},
    hashed_id: "{$compareList->getHashedId()}"
  ) {
    result
    compareProductsList {
      items {
        item_id,
        product {
          sku
        }
      }
    }
  }
}
MUTATION;

        $response = $this->graphQlQuery($mutation, [], '', $headerMap);

        $this->assertArrayHasKey('addProductToCompareList', $response);
        $this->assertInternalType('array', $response['addProductToCompareList']);
        $this->assertArrayHasKey('result', $response['addProductToCompareList']);
        $this->assertTrue($response['addProductToCompareList']['result']);

        $this->assertArrayHasKey('compareProductsList', $response['addProductToCompareList']);
        $this->assertArrayHasKey('items', $response['addProductToCompareList']['compareProductsList']);
        $this->assertCount(2, $response['addProductToCompareList']['compareProductsList']['items']);

        $this->assertArrayHasKey('item_id', $response['addProductToCompareList']['compareProductsList']['items'][0]);
        $this->assertSame(
            'simple',
            $response['addProductToCompareList']['compareProductsList']['items'][0]['product']['sku']
        );

        $this->assertArrayHasKey('item_id', $response['addProductToCompareList']['compareProductsList']['items'][1]);
        $this->assertSame(
            'virtual-product',
            $response['addProductToCompareList']['compareProductsList']['items'][1]['product']['sku']
        );
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     */
    public function testAddProductToCompareListWithoutAuthorization()
    {
        /** @var CreateList $createList */
        $createList = Bootstrap::getObjectManager()->get(CreateList::class);
        $compareList = $createList->execute(1);

        $mutation
            = <<<MUTATION
mutation {
  addProductToCompareList(
    input: {ids: [1, 21]},
    hashed_id: "{$compareList->getHashedId()}"
  ) {
    result
    compareProductsList {
      items {
        item_id,
        product {
          sku
        }
      }
    }
  }
}
MUTATION;

        $response = $this->graphQlQuery($mutation);

        $this->assertArrayHasKey('addProductToCompareList', $response);
        $this->assertInternalType('array', $response['addProductToCompareList']);
        $this->assertArrayHasKey('result', $response['addProductToCompareList']);
        $this->assertTrue($response['addProductToCompareList']['result']);

        $this->assertArrayHasKey('compareProductsList', $response['addProductToCompareList']);
        $this->assertArrayHasKey('items', $response['addProductToCompareList']['compareProductsList']);
        $this->assertCount(0, $response['addProductToCompareList']['compareProductsList']['items']);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     */
    public function testAddProductToCompareListForGuest()
    {
        /** @var CreateList $createList */
        $createList = Bootstrap::getObjectManager()->get(CreateList::class);
        $compareList = $createList->execute(0);

        $mutation
            = <<<MUTATION
mutation {
  addProductToCompareList(
    input: {ids: [1, 21]},
    hashed_id: "{$compareList->getHashedId()}"
  ) {
    result
    compareProductsList {
      items {
        item_id,
        product {
          sku
        }
      }
    }
  }
}
MUTATION;
        $response = $this->graphQlQuery($mutation);

        $this->assertArrayHasKey('addProductToCompareList', $response);
        $this->assertInternalType('array', $response['addProductToCompareList']);
        $this->assertArrayHasKey('result', $response['addProductToCompareList']);
        $this->assertTrue($response['addProductToCompareList']['result']);

        $this->assertArrayHasKey('compareProductsList', $response['addProductToCompareList']);
        $this->assertArrayHasKey('items', $response['addProductToCompareList']['compareProductsList']);
        $this->assertCount(2, $response['addProductToCompareList']['compareProductsList']['items']);

        $this->assertArrayHasKey('item_id', $response['addProductToCompareList']['compareProductsList']['items'][0]);
        $this->assertSame(
            'simple',
            $response['addProductToCompareList']['compareProductsList']['items'][0]['product']['sku']
        );

        $this->assertArrayHasKey('item_id', $response['addProductToCompareList']['compareProductsList']['items'][1]);
        $this->assertSame(
            'virtual-product',
            $response['addProductToCompareList']['compareProductsList']['items'][1]['product']['sku']
        );
    }
}

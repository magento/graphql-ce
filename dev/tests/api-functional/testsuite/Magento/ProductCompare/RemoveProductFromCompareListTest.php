<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\ProductCompare;

use Magento\Catalog\Model\Product\Compare\AddToList;
use Magento\Catalog\Model\Product\Compare\CreateList;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class RemoveProductFromCompareListTest extends GraphQlAbstract
{
    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     */
    public function testRemoveProductFromCompareList()
    {
        /** @var CreateList $createList */
        $createList = Bootstrap::getObjectManager()->get(CreateList::class);
        /** @var AddToList $addToList */
        $addToList = Bootstrap::getObjectManager()->get(AddToList::class);
        $customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);

        $customerToken = $customerTokenService->createCustomerAccessToken('customer@example.com', 'password');
        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];

        $compareList = $createList->execute(1);
        $addToList->execute(1, $compareList->getHashedId(), 1);
        $addToList->execute(1, $compareList->getHashedId(), 21);

        $mutation
            = <<<MUTATION
mutation {
  removeProductFromCompareList(
    input: {ids: [1]},
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

        $this->assertArrayHasKey('removeProductFromCompareList', $response);
        $this->assertInternalType('array', $response['removeProductFromCompareList']);
        $this->assertArrayHasKey('result', $response['removeProductFromCompareList']);
        $this->assertTrue($response['removeProductFromCompareList']['result']);

        $this->assertArrayHasKey('compareProductsList', $response['removeProductFromCompareList']);
        $this->assertArrayHasKey('items', $response['removeProductFromCompareList']['compareProductsList']);
        $this->assertCount(1, $response['removeProductFromCompareList']['compareProductsList']['items']);

        $this->assertArrayHasKey('item_id', $response['removeProductFromCompareList']['compareProductsList']['items'][0]);
        $this->assertSame(
            'virtual-product',
            $response['removeProductFromCompareList']['compareProductsList']['items'][0]['product']['sku']
        );
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     */
    public function testRemoveProductFromCompareListWithoutAuthorization()
    {
        /** @var CreateList $createList */
        $createList = Bootstrap::getObjectManager()->get(CreateList::class);
        /** @var AddToList $addToList */
        $addToList = Bootstrap::getObjectManager()->get(AddToList::class);

        $compareList = $createList->execute(1);
        $addToList->execute(1, $compareList->getHashedId(), 1);
        $addToList->execute(1, $compareList->getHashedId(), 21);

        $mutation
            = <<<MUTATION
mutation {
  removeProductFromCompareList(
    input: {ids: [1]},
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

        $this->assertArrayHasKey('removeProductFromCompareList', $response);
        $this->assertInternalType('array', $response['removeProductFromCompareList']);
        $this->assertArrayHasKey('result', $response['removeProductFromCompareList']);
        $this->assertTrue($response['removeProductFromCompareList']['result']);

        $this->assertArrayHasKey('compareProductsList', $response['removeProductFromCompareList']);
        $this->assertArrayHasKey('items', $response['removeProductFromCompareList']['compareProductsList']);
        $this->assertCount(0, $response['removeProductFromCompareList']['compareProductsList']['items']);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     */
    public function testRemoveProductFromCompareListForGuest()
    {
        /** @var CreateList $createList */
        $createList = Bootstrap::getObjectManager()->get(CreateList::class);
        /** @var AddToList $addToList */
        $addToList = Bootstrap::getObjectManager()->get(AddToList::class);

        $compareList = $createList->execute(0);
        $addToList->execute(0, $compareList->getHashedId(), 1);
        $addToList->execute(0, $compareList->getHashedId(), 21);

        $mutation
            = <<<MUTATION
mutation {
  removeProductFromCompareList(
    input: {ids: [1]},
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

        $this->assertArrayHasKey('removeProductFromCompareList', $response);
        $this->assertInternalType('array', $response['removeProductFromCompareList']);
        $this->assertArrayHasKey('result', $response['removeProductFromCompareList']);
        $this->assertTrue($response['removeProductFromCompareList']['result']);

        $this->assertArrayHasKey('compareProductsList', $response['removeProductFromCompareList']);
        $this->assertArrayHasKey('items', $response['removeProductFromCompareList']['compareProductsList']);
        $this->assertCount(1, $response['removeProductFromCompareList']['compareProductsList']['items']);

        $this->assertArrayHasKey('item_id', $response['removeProductFromCompareList']['compareProductsList']['items'][0]);
        $this->assertSame(
            'virtual-product',
            $response['removeProductFromCompareList']['compareProductsList']['items'][0]['product']['sku']
        );
    }
}

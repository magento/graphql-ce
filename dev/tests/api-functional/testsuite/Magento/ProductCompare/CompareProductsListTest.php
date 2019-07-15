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

class CompareProductsListTest extends GraphQlAbstract
{
    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testCompareProductsList()
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

        $mutation
            = <<<QUERY
query {
  compareProductsList(
    hashed_id: "{$compareList->getHashedId()}"
  )
  {
    items {
      product {
        sku
      }
      
    }
  }
}
QUERY;

        $response = $this->graphQlQuery($mutation, [], '', $headerMap);

        $this->assertArrayHasKey('compareProductsList', $response);
        $this->assertInternalType('array', $response['compareProductsList']);

        $this->assertArrayHasKey('items', $response['compareProductsList']);
        $this->assertCount(1, $response['compareProductsList']['items']);

        $this->assertSame(
            'simple',
            $response['compareProductsList']['items'][0]['product']['sku']
        );
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testCompareProductsListWithoutAuthorization()
    {
        /** @var CreateList $createList */
        $createList = Bootstrap::getObjectManager()->get(CreateList::class);
        /** @var AddToList $addToList */
        $addToList = Bootstrap::getObjectManager()->get(AddToList::class);

        $compareList = $createList->execute(1);
        $addToList->execute(1, $compareList->getHashedId(), 1);

        $mutation
            = <<<QUERY
query {
  compareProductsList(
    hashed_id: "{$compareList->getHashedId()}"
  )
  {
    items {
      product {
        sku
      }
      
    }
  }
}
QUERY;

        $response = $this->graphQlQuery($mutation);

        $this->assertArrayHasKey('compareProductsList', $response);
        $this->assertInternalType('array', $response['compareProductsList']);

        $this->assertArrayHasKey('items', $response['compareProductsList']);
        $this->assertCount(0, $response['compareProductsList']['items']);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testCompareProductsListForGuest()
    {
        /** @var CreateList $createList */
        $createList = Bootstrap::getObjectManager()->get(CreateList::class);
        /** @var AddToList $addToList */
        $addToList = Bootstrap::getObjectManager()->get(AddToList::class);

        $compareList = $createList->execute(0);
        $addToList->execute(0, $compareList->getHashedId(), 1);

        $mutation
            = <<<QUERY
query {
  compareProductsList(
    hashed_id: "{$compareList->getHashedId()}"
  )
  {
    items {
      product {
        sku
      }
      
    }
  }
}
QUERY;

        $response = $this->graphQlQuery($mutation);

        $this->assertArrayHasKey('compareProductsList', $response);
        $this->assertInternalType('array', $response['compareProductsList']);

        $this->assertArrayHasKey('items', $response['compareProductsList']);
        $this->assertCount(1, $response['compareProductsList']['items']);

        $this->assertSame(
            'simple',
            $response['compareProductsList']['items'][0]['product']['sku']
        );
    }
}

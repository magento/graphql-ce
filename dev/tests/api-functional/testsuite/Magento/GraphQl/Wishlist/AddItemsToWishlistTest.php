<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Wishlist;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\Helper\Bootstrap;

class AddItemsToWishlistTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    protected function setUp()
    {
        parent::setUp();

        $this->customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Wishlist/_files/wishlist.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     */
    public function testAddItemToWishlist(): void
    {
        $sku = 'simple_product';
        $query = <<<QUERY
mutation {
  addItemsToWishlist (
    input: {
      sku_list: ["$sku"]
    }
  ) {
    wishlist {
      items {
        product {
          sku
        }
      }
      items_count
    }
  }
}
QUERY;

        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());

        self::assertArrayHasKey('wishlist', $response['addItemsToWishlist']);
        $wishlistResponsePath = $response['addItemsToWishlist']['wishlist'];
        self::assertEquals(1, $wishlistResponsePath['items_count']);
        self::assertCount(1, $wishlistResponsePath['items']);
        self::assertEquals($sku, $wishlistResponsePath['items'][0]['product']['sku']);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Wishlist/_files/wishlist.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @expectedException \Exception
     * @expectedExceptionMessage You must be logged in to use wishlist
     */
    public function testAddItemToWishlistForGuest(): void
    {
        $sku = 'simple_product';
        $query = <<<QUERY
mutation {
  addItemsToWishlist (
    input: {
      sku_list: ["$sku"]
    }
  ) {
    wishlist {
      items {
        product {
          sku
        }
      }
      items_count
    }
  }
}
QUERY;

        $this->graphQlMutation($query);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Wishlist/_files/wishlist.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/virtual_product.php
     */
    public function testAddMultipleItemsToWishlist()
    {
        $skuList = ['simple_product', 'virtual_product'];
        $skuListString = '"simple_product", "virtual_product"';
        $query = <<<QUERY
mutation {
  addItemsToWishlist (
    input: {
      sku_list: [$skuListString]
    }
  ) {
    wishlist {
      items {
        product {
          sku
        }
      }
      items_count
    }
  }
}
QUERY;

        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());

        $wishlistResponsePath = $response['addItemsToWishlist']['wishlist'];
        self::assertEquals(count($skuList), $wishlistResponsePath['items_count']);
        self::assertCount(count($skuList), $wishlistResponsePath['items']);
        self::assertEquals($skuList[0], $wishlistResponsePath['items'][0]['product']['sku']);
        self::assertEquals($skuList[1], $wishlistResponsePath['items'][1]['product']['sku']);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Wishlist/_files/wishlist.php
     * @expectedException \Exception
     * @expectedExceptionMessage Cannot add the specified items to wishlist
     * @group latest
     */
    public function testAddNonExistentProductToWishlist(): void
    {
        $sku = 'non_existent_product';
        $query = <<<QUERY
mutation {
  addItemsToWishlist (
    input: {
      sku_list: ["$sku"]
    }
  ) {
    wishlist {
      items {
        product {
          sku
        }
      }
      items_count
    }
  }
}
QUERY;

        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     * Retrieve customer authorization headers
     *
     * @param string $username
     * @param string $password
     * @return array
     * @throws AuthenticationException
     */
    private function getHeaderMap(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);
        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        return $headerMap;
    }
}

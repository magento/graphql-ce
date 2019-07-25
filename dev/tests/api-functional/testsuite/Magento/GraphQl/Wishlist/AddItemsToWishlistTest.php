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
        $items =[
            [
                'sku' => 'simple_product',
                'quantity' =>  5
            ]
        ];

        $query = $this->getAddItemsQuery($items);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());

        self::assertArrayHasKey('wishlist', $response['addItemsToWishlist']);
        $wishlistResponsePath = $response['addItemsToWishlist']['wishlist'];
        self::assertEquals(1, $wishlistResponsePath['items_count']);
        self::assertCount(1, $wishlistResponsePath['items']);
        self::assertEquals($items[0]['quantity'], $wishlistResponsePath['items'][0]['qty']);
        self::assertEquals($items[0]['sku'], $wishlistResponsePath['items'][0]['product']['sku']);
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
        $items =[
            [
                'sku' => 'simple_product',
                'quantity' =>  5
            ]
        ];

        $query = $this->getAddItemsQuery($items);
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
        $items =[
            [
                'sku' => 'simple_product',
                'quantity' =>  5
            ],
            [
                'sku' => 'virtual_product',
                'quantity' =>  5
            ]
        ];

        $query = $this->getAddItemsQuery($items);
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());

        $wishlistResponsePath = $response['addItemsToWishlist']['wishlist'];
        self::assertEquals(count($items), $wishlistResponsePath['items_count']);
        self::assertCount(count($items), $wishlistResponsePath['items']);
        self::assertEquals($items[0]['sku'], $wishlistResponsePath['items'][0]['product']['sku']);
        self::assertEquals($items[1]['sku'], $wishlistResponsePath['items'][1]['product']['sku']);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Wishlist/_files/wishlist.php
     * @expectedException \Exception
     * @expectedExceptionMessage Cannot add the specified items to wishlist
     */
    public function testAddNonExistentProductToWishlist(): void
    {
        $items =[
            [
                'sku' => 'non_existent_product',
                'quantity' =>  5
            ]
        ];

        $query = $this->getAddItemsQuery($items);
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     * Returns GraphQl query for adding items to wishlist
     *
     * @param array $items
     * @return string
     */
    private function getAddItemsQuery(array $items): string
    {
        $itemsFragment = '';
        foreach ($items as $item) {
            $itemsFragment .= "{ sku: \"{$item['sku']}\" quantity: {$item['quantity']} },";
        }
       // $itemsFragment = rtrim($itemsFragment, ',');

        return <<<QUERY
mutation {
  addItemsToWishlist (
    input: {
      wishlist_items: [$itemsFragment]
    }
  ) {
    wishlist {
      items {
        qty
        product {
          sku
        }
      }
      items_count
    }
  }
}
QUERY;
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

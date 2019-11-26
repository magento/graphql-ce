<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Wishlist;

use Exception;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Wishlist\Model\WishlistFactory;

class RemoveItemsFromWishlistTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * Verify, removeProductsFromWishlist will remove specified wishlist item.
     *
     * @magentoApiDataFixture Magento/Wishlist/_files/wishlist.php
     * @return void
     */
    public function testRemoveProductFromWishList(): void
    {
        $wishlist = $this->wishlistFactory->create();
        $wishlist->loadByCustomerId(1);
        $itemId = $wishlist->getItemCollection()->getFirstItem()->getId();
        $wishListId = $wishlist->getId();
        $query =
            <<<QUERY
mutation  {
  removeProductsFromWishlist(
    input: {
      wishlist_id: "{$wishListId}"
      wishlist_items_ids: [$itemId]
    }
  ) {
    wishlist {
      id
      items {
        id
        product {
          id
          name
        }
      }
      items_count
      sharing_code
      updated_at
    }
  }
}
QUERY;
        $response = $this->graphQlMutation($query, [], '', $this->getHeaderMap());
        $this->assertEmpty($response['removeProductsFromWishlist']['wishlist']['items']);
    }

    /**
     * Retrieve customer authorization headers.
     *
     * @param string $username
     * @param string $password
     * @return array
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @expectedException Exception
     * @expectedExceptionMessage The current customer isn't authorized"
     */

    private function getHeaderMap(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);
        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        return $headerMap;
    }

    /**
     * Verify, testRemoveProductsFromWishListWithoutAuthorizationHeaders will return correct message.
     *
     * @magentoApiDataFixture Magento/Wishlist/_files/wishlist.php
     * @return void
     * @expectedException Exception
     * @expectedExceptionMessage The current customer isn't authorized.
     */

    public function testRemoveProductsFromWishListWithoutAuthorizationHeaders(): void
    {
        $wishlist = $this->wishlistFactory->create();
        $wishlist->loadByCustomerId(2);
        $itemId = $wishlist->getItemCollection()->getFirstItem()->getId();
        $wishListId = $wishlist->getId();
        $query =
            <<<QUERY
mutation  {
  removeProductsFromWishlist(
    input: {
      wishlist_id: "{$wishListId}"
      wishlist_items_ids: [$itemId]
    }
  ) {
    wishlist {
      id
      items {
        id
        product {
          id
          name
        }
      }
      items_count
      sharing_code
      updated_at
    }
  }
}
QUERY;
        $this->graphQlMutation($query, [], '', $this->getWrongHeaderMap());
    }

    private function getWrongHeaderMap()
    {
        $headerMap = ['Authorization' => 'Bearer not_existing_token_id'];
        return $headerMap;
    }

    /**
     * Verify, testRemoveProductsFromWishListWithNonExistingWishlistId will return correct message.
     * @todo Refactor this test after the problem mentioned in
     * https://github.com/magento/graphql-ce/pull/1043#issuecomment-558530493 is fixed
     *
     * @magentoApiDataFixture Magento/Wishlist/_files/wishlist.php
     * @return void
     * @expectedException Exception
     * @expectedExceptionMessage The current customer isn't authorized.
    */
    public function testRemoveProductsFromWishListWithNonExistingWishlistId(): void
    {
        $wishlist = $this->wishlistFactory->create();
        $wishlist->loadByCustomerId(3);
        $itemId = $wishlist->getItemCollection()->getFirstItem()->getWrongId();
        $wishListId = $wishlist->getWrongId();
        $query =
            <<<QUERY
mutation  {
  removeProductsFromWishlist(
    input: {
      wishlist_id: "{$wishListId}"
      wishlist_items_ids: [$itemId]
    }
  ) {
    wishlist {
      id
      items {
        id
        product {
          id
          name
        }
      }
      items_count
      sharing_code
      updated_at
    }
  }
}
QUERY;
        $this->graphQlMutation($query, [], '', $this->getHeaderMap());
    }

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
        $this->wishlistFactory = Bootstrap::getObjectManager()->get(WishlistFactory::class);
    }
}

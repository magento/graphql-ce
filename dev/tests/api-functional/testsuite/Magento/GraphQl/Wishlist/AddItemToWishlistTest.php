<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Wishlist;

use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\Item;
use Magento\TestFramework\Helper\Bootstrap;

class AddItemToWishlistTest extends GraphQlAbstract
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
     * @magentoApiDataFixture Magento/Wishlist/_files/wishlist.php
     * @throws \Magento\Framework\Exception\AuthenticationException
     * @throws \Exception
     */
    public function testAddItemToWishlist(): void
    {
        /** @var Wishlist $wishlist */
        $wishlist = Bootstrap::getObjectManager()->create(
            Wishlist::class
        );
        $wishlist->loadByCustomerId(1, true);
        /** @var Item $wishlistItem */
        $wishlistItem = $wishlist->getItemCollection()->getFirstItem();
        $wishlist->addNewItem(1);
        $mutation =
            <<<MUTATION
mutation {
  addItemsToWishlist (
    input: {
      skus: ["simple"]
    }
  ) {
    wishlist {
      items {
        id
        description
        qty
      }
      sharing_code
      updated_at
      items_count
      name
    }
  }
}
MUTATION;

        $response = $this->graphQlQuery($mutation, [], '', $this->getCustomerAuthHeaders('customer@example.com', 'password'));
        $this->assertEquals($wishlist->getSharingCode(), $response['addItemsToWishlist']['wishlist']['sharing_code']);
        $this->assertEquals($wishlist->getItemsCount(), $response['addItemsToWishlist']['wishlist']['items_count']);
        $this->assertEquals($wishlist->getName(), $response['addItemsToWishlist']['wishlist']['name']);
        $this->assertEquals($wishlistItem->getData('qty') + 1, $response['addItemsToWishlist']['wishlist']['items'][0]['qty']);
        $this->assertEquals($wishlistItem->getDescription(), $response['addItemsToWishlist']['wishlist']['items'][0]['description']);
    }

    /**
     * @magentoApiDataFixture Magento/Wishlist/_files/wishlist.php
     * @magentoApiDataFixture Magento/Catalog/_files/multiple_products.php
     * @throws \Exception
     */
    public function testAddMultipleProductsToWishlist()
    {
        /** @var Wishlist $wishlist */
        $wishlist = Bootstrap::getObjectManager()->create(
            Wishlist::class
        );
        $wishlist->loadByCustomerId(1, true);
        $wishlist->addNewItem( 10);
        $wishlist->addNewItem( 11);
        $mutation =
            <<<MUTATION
mutation {
  addItemsToWishlist (
    input: {
      skus: ["simple1", "simple2"]
    }
  ) {
    wishlist {
      items {
        id
        description
        qty
      }
      sharing_code
      updated_at
      items_count
      name
    }
  }
}
MUTATION;

        $response = $this->graphQlQuery($mutation, [], '', $this->getCustomerAuthHeaders('customer@example.com', 'password'));
        $this->assertEquals($wishlist->getItemsCount(), $response['addItemsToWishlist']['wishlist']['items_count']);
    }

    /**
     * No products would be added to wishlist in this case.
     *
     * @magentoApiDataFixture Magento/Wishlist/_files/wishlist.php
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    public function testAddNotExistedProductToWishlist()
    {
        /** @var Wishlist $wishlist */
        $wishlist = Bootstrap::getObjectManager()->create(
            Wishlist::class
        );
        $wishlist->loadByCustomerId(1, true);
        $mutation =
            <<<MUTATION
mutation {
  addItemsToWishlist (
    input: {
      skus: ["non-existed-product"]
    }
  ) {
    wishlist {
      items {
        id
        description
        qty
      }
      sharing_code
      updated_at
      items_count
      name
    }
  }
}
MUTATION;
        $response = $this->graphQlQuery($mutation, [], '', $this->getCustomerAuthHeaders('customer@example.com', 'password'));
        $this->assertEquals($wishlist->getItemsCount(), $response['addItemsToWishlist']['wishlist']['items_count']);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     * @throws \Exception
     */
    public function testAddProductToWishlistForNotAuthorizedCustomer()
    {
        $mutation =
            <<<MUTATION
mutation {
  addItemsToWishlist (
    input: {
      skus: ["simple"]
    }
  ) {
    wishlist {
      items {
        id
        qty
      }
      items_count
    }
  }
}
MUTATION;

        self::expectExceptionMessage(
            'Cannot get a wish list for the specified Customer ID'
        );
        $this->graphQlQuery($mutation);
    }

    /**
     * @magentoApiDataFixture Magento/Wishlist/_files/wishlist.php
     * @throws \Exception
     * @throws \Magento\Framework\Exception\AuthenticationException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testAddDuplicateProductToWishlist()
    {
        /** @var Wishlist $wishlist */
        $wishlist = Bootstrap::getObjectManager()->create(
            Wishlist::class
        );
        $wishlist->loadByCustomerId(1, true);
        /** @var Item $wishlistItem */
        $wishlistItem = $wishlist->getItemCollection()->getFirstItem();
        $wishlist->addNewItem(1);
        $wishlist->addNewItem(1);
        $mutation =
            <<<MUTATION
mutation {
  addItemsToWishlist (
    input: {
      skus: ["simple"]
    }
  ) {
    wishlist {
      items {
        qty
      }
      items_count
    }
  }
}
MUTATION;

        $response = $this->graphQlQuery($mutation, [], '', $this->getCustomerAuthHeaders('customer@example.com', 'password'));
        $this->assertEquals($wishlist->getItemsCount(), $response['addItemsToWishlist']['wishlist']['items_count']);
        $this->assertEquals($wishlistItem->getData('qty') + 1, $response['addItemsToWishlist']['wishlist']['items'][0]['qty']);
    }

    /**
     * @param string $email
     * @param string $password
     * @return array
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    private function getCustomerAuthHeaders(string $email, string $password): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($email, $password);
        return ['Authorization' => 'Bearer ' . $customerToken];
    }
}

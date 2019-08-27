<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Wishlist;

use Exception;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Model\Configuration;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResourceModel;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;

class AddProductToWishlistTest extends GraphQlAbstract
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
     * @var WishlistResourceModel
     */
    private $wishlistResource;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerResourceModel
     */
    private $customerResource;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitConfig;

    /**
     * @var Config
     */
    private $resourceConfig;

    protected function setUp()
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
        $this->wishlistFactory = $objectManager->get(WishlistFactory::class);
        $this->wishlistResource = $objectManager->get(WishlistResourceModel::class);
        $this->productFactory = $objectManager->get(ProductFactory::class);
        $this->customerFactory = $objectManager->get(CustomerFactory::class);
        $this->customerResource = $objectManager->get(CustomerResourceModel::class);
        $this->scopeConfig = $objectManager->get(ScopeConfigInterface::class);
        $this->reinitConfig = $objectManager->get(ReinitableConfigInterface::class);
        $this->resourceConfig = $objectManager->get(Config::class);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testAddProductToWishlist(): void
    {
        $productSku = "simple";

        $query =
            <<<QUERY
mutation {
  addSimpleProductsToWishlist (
    input: {
      wishlist_items: [
        {
          data: {
            quantity: 1
            sku: "{$productSku}"
          }
        }
      ]
    }
  ) {
    items_count
    name
    sharing_code
    updated_at
    items {
      id
      qty
      description
      added_at
      product {
        sku
        name
      }
    }
  }
}
QUERY;

        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $this->getCustomerAuthHeaders('customer@example.com', 'password')
        );

        /** @var Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($wishlist, 1, 'customer_id');

        /** @var Item $wishlistItem */
        $wishlistItem = $wishlist->getItemCollection()->getFirstItem();
        $wishlistItemProduct = $wishlistItem->getProduct();

        $this->assertEquals($wishlist->getItemsCount(), $response['addSimpleProductsToWishlist']['items_count']);
        $this->assertEquals($wishlist->getName(), $response['addSimpleProductsToWishlist']['name']);
        $this->assertEquals($wishlist->getSharingCode(), $response['addSimpleProductsToWishlist']['sharing_code']);
        $this->assertEquals($wishlist->getUpdatedAt(), $response['addSimpleProductsToWishlist']['updated_at']);

        $this->assertEquals($wishlistItem->getId(), $response['addSimpleProductsToWishlist']['items'][0]['id']);
        $this->assertEquals(
            $wishlistItem->getData('qty'),
            $response['addSimpleProductsToWishlist']['items'][0]['qty']
        );
        $this->assertEquals(
            $wishlistItem->getDescription(),
            $response['addSimpleProductsToWishlist']['items'][0]['description']
        );
        $this->assertEquals(
            $wishlistItem->getAddedAt(),
            $response['addSimpleProductsToWishlist']['items'][0]['added_at']
        );

        $this->assertEquals(
            $wishlistItemProduct->getSku(),
            $response['addSimpleProductsToWishlist']['items'][0]['product']['sku']
        );
        $this->assertEquals(
            $wishlistItemProduct->getName(),
            $response['addSimpleProductsToWishlist']['items'][0]['product']['name']
        );
    }

    /**
     * @magentoApiDataFixture Magento/Wishlist/_files/wishlist.php
     */
    public function testIncrementQtyProductToWishlist(): void
    {
        $productSku = "simple";

        $query =
            <<<QUERY
mutation {
  addSimpleProductsToWishlist (
    input: {
      wishlist_items: [
        {
          data: {
            quantity: 1
            sku: "{$productSku}"
          }
        }
      ]
    }
  ) {
    items_count
    name
    sharing_code
    updated_at
    items {
      id
      qty
      description
      added_at
      product {
        sku
        name
      }
    }
  }
}
QUERY;

        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $this->getCustomerAuthHeaders('customer@example.com', 'password')
        );

        /** @var Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($wishlist, 1, 'customer_id');

        /** @var Item $wishlistItem */
        $wishlistItem = $wishlist->getItemCollection()->getFirstItem();
        $wishlistItemProduct = $wishlistItem->getProduct();

        $this->assertEquals($wishlist->getItemsCount(), $response['addSimpleProductsToWishlist']['items_count']);
        $this->assertEquals($wishlist->getName(), $response['addSimpleProductsToWishlist']['name']);
        $this->assertEquals($wishlist->getSharingCode(), $response['addSimpleProductsToWishlist']['sharing_code']);
        $this->assertEquals($wishlist->getUpdatedAt(), $response['addSimpleProductsToWishlist']['updated_at']);

        $this->assertEquals($wishlistItem->getId(), $response['addSimpleProductsToWishlist']['items'][0]['id']);
        $this->assertEquals(2, $response['addSimpleProductsToWishlist']['items'][0]['qty']);
        $this->assertEquals(
            $wishlistItem->getDescription(),
            $response['addSimpleProductsToWishlist']['items'][0]['description']
        );
        $this->assertEquals(
            $wishlistItem->getAddedAt(),
            $response['addSimpleProductsToWishlist']['items'][0]['added_at']
        );

        $this->assertEquals(
            $wishlistItemProduct->getSku(),
            $response['addSimpleProductsToWishlist']['items'][0]['product']['sku']
        );
        $this->assertEquals(
            $wishlistItemProduct->getName(),
            $response['addSimpleProductsToWishlist']['items'][0]['product']['name']
        );
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple_out_of_stock.php
     */
    public function testAddProductToWishlistWithoutStock(): void
    {
        $showOutOfStock = $this->scopeConfig->getValue(Configuration::XML_PATH_SHOW_OUT_OF_STOCK);

        $this->resourceConfig->saveConfig(Configuration::XML_PATH_SHOW_OUT_OF_STOCK, 1);
        $this->reinitConfig->reinit();
        $productSku = "simple-out-of-stock";

        $query =
            <<<QUERY
mutation {
  addSimpleProductsToWishlist (
    input: {
      wishlist_items: [
        {
          data: {
            quantity: 1
            sku: "{$productSku}"
          }
        }
      ]
    }
  ) {
    items_count
    name
    sharing_code
    updated_at
    items {
      id
      qty
      description
      added_at
      product {
        sku
        name
      }
    }
  }
}
QUERY;

        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $this->getCustomerAuthHeaders('customer@example.com', 'password')
        );

        /** @var Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($wishlist, 1, 'customer_id');

        /** @var Item $wishlistItem */
        $wishlistItem = $wishlist->getItemCollection()->getFirstItem();
        $wishlistItemProduct = $wishlistItem->getProduct();

        $this->assertEquals($wishlist->getItemsCount(), $response['addSimpleProductsToWishlist']['items_count']);
        $this->assertEquals($wishlist->getName(), $response['addSimpleProductsToWishlist']['name']);
        $this->assertEquals($wishlist->getSharingCode(), $response['addSimpleProductsToWishlist']['sharing_code']);
        $this->assertEquals($wishlist->getUpdatedAt(), $response['addSimpleProductsToWishlist']['updated_at']);

        $this->assertEquals($wishlistItem->getId(), $response['addSimpleProductsToWishlist']['items'][0]['id']);
        $this->assertEquals(1, $response['addSimpleProductsToWishlist']['items'][0]['qty']);
        $this->assertEquals(
            $wishlistItem->getDescription(),
            $response['addSimpleProductsToWishlist']['items'][0]['description']
        );
        $this->assertEquals(
            $wishlistItem->getAddedAt(),
            $response['addSimpleProductsToWishlist']['items'][0]['added_at']
        );

        $this->assertEquals(
            $wishlistItemProduct->getSku(),
            $response['addSimpleProductsToWishlist']['items'][0]['product']['sku']
        );
        $this->assertEquals(
            $wishlistItemProduct->getName(),
            $response['addSimpleProductsToWishlist']['items'][0]['product']['name']
        );
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     * @expectedException Exception
     * @expectedExceptionMessage The current user cannot perform operations on wishlist
     */
    public function testGuestAddProductToWishlist()
    {
        $productSku = "simple";

        $query =
            <<<QUERY
mutation {
  addSimpleProductsToWishlist (
    input: {
      wishlist_items: [
        {
          data: {
            quantity: 1
            sku: "{$productSku}"
          }
        }
      ]
    }
  ) {
    items_count
    name
    sharing_code
    updated_at
    items {
      id
      qty
      description
      added_at
      product {
        sku
        name
      }
    }
  }
}
QUERY;
        $this->graphQlMutation($query);
    }

    /**
     * @param string $email
     * @param string $password
     * @return array
     * @throws AuthenticationException
     */
    private function getCustomerAuthHeaders(string $email, string $password): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($email, $password);
        return ['Authorization' => 'Bearer ' . $customerToken];
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See≤ COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Multishipping;

use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\ObjectManager;

/**
 * Test for set shipping addresses on cart mutation
 */
class SetShippingAddressesOnCartTest extends GraphQlAbstract
{
    /**
     * @var QuoteResource
     */
    private $quoteResource;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedId;

    protected function setUp()
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->quoteResource = $objectManager->create(QuoteResource::class);
        $this->quote = $objectManager->create(Quote::class);
        $this->quoteIdToMaskedId = $objectManager->create(QuoteIdToMaskedQuoteIdInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_simple_product_saved.php
     */
    public function testSetGuestShippingAddressesOnCart()
    {
        $this->quoteResource->load(
            $this->quote,
            'test_order_with_simple_product_without_address',
            'reserved_order_id'
        );
        $maskedQuoteId = $this->quoteIdToMaskedId->execute((int)$this->quote->getId());

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          customer_address_id: 2
          cart_items: [{ cart_item_id: 1, quantity: 1 }]
        },
        {
          customer_address_id: 1
          cart_items: [{ cart_item_id: 2, quantity: 1 }]
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        firstname
        lastname
        company
        street
        city
        postcode
        telephone
      }
    }
  }
}
QUERY;
        self::expectExceptionMessage('GraphQL response contains errors: The current customer isn\'t authorized.');
        $this->graphQlQuery($query);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_simple_product_saved.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Customer/_files/customer_two_addresses.php
     */
    public function testSetRegisteredCustomerShippingAddressesOnCart()
    {
        $this->quoteResource->load(
            $this->quote,
            'test_order_with_simple_product_without_address',
            'reserved_order_id'
        );
        $maskedQuoteId = $this->quoteIdToMaskedId->execute((int)$this->quote->getId());
        $this->quoteResource->load(
            $this->quote,
            'test_order_with_simple_product_without_address',
            'reserved_order_id'
        );
        $this->quote->setCustomerId(1);
        $this->quoteResource->save($this->quote);
        /** @var CartItemInterface $cartItem */
        $cartItemsCollection = $this->quote->getItemsCollection();
        $cartItem = current($cartItemsCollection->getItems());
        $cartItemId = (int)$cartItem->getItemId();

        $headerMap = $this->getHeaderMap();

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          customer_address_id: 1
          cart_items: [{ cart_item_id: $cartItemId, quantity: 1 }]
        },
        {
          customer_address_id: 2
          cart_items: [{ cart_item_id: $cartItemId, quantity: 2 }]
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        firstname
        lastname
        company
        street
        city
        postcode
        telephone
        cart_items {
          cart_item_id
          quantity
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query, [], '', $headerMap);

        self::assertArrayHasKey('cart', $response['setShippingAddressesOnCart']);
        $cartResponse = $response['setShippingAddressesOnCart']['cart'];
        self::assertArrayHasKey('shipping_addresses', $cartResponse);
        $shippingAddressesResponse = $cartResponse['shipping_addresses'];
        self::assertEquals(2, count($shippingAddressesResponse));
        $this->assertFirstShippingAddressFields($shippingAddressesResponse[0], $cartItemId);
        $this->assertSecondShippingAddressFields($shippingAddressesResponse[1], $cartItemId);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_simple_product_saved.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Customer/_files/customer_two_addresses.php
     */
    public function testSetRegisteredCustomerWrongShippingAddressesOnCart()
    {
        $this->quoteResource->load(
            $this->quote,
            'test_order_with_simple_product_without_address',
            'reserved_order_id'
        );
        $maskedQuoteId = $this->quoteIdToMaskedId->execute((int)$this->quote->getId());
        $this->quoteResource->load(
            $this->quote,
            'test_order_with_simple_product_without_address',
            'reserved_order_id'
        );
        $this->quote->setCustomerId(1);
        $this->quoteResource->save($this->quote);
        /** @var CartItemInterface $cartItem */
        $cartItemsCollection = $this->quote->getItemsCollection();
        $cartItem = current($cartItemsCollection->getItems());
        $cartItemId = $cartItem->getItemId();

        $headerMap = $this->getHeaderMap();

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          customer_address_id: 100500
          cart_items: [{ cart_item_id: $cartItemId, quantity: 1 }]
        },
        {
          customer_address_id: 100501
          cart_items: [{ cart_item_id: $cartItemId, quantity: 1 }]
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        firstname
        lastname
        company
        street
        city
        postcode
        telephone
      }
    }
  }
}
QUERY;
        self::expectExceptionMessage('Verify the shipping address information and continue.');
        $this->graphQlQuery($query, [], '', $headerMap);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_simple_product_saved.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Customer/_files/customer_two_addresses.php
     */
    public function testSetRegisteredCustomerShippingAddressesWithWrongItemsOnCart()
    {
        $this->quoteResource->load(
            $this->quote,
            'test_order_with_simple_product_without_address',
            'reserved_order_id'
        );
        $maskedQuoteId = $this->quoteIdToMaskedId->execute((int)$this->quote->getId());
        $this->quoteResource->load(
            $this->quote,
            'test_order_with_simple_product_without_address',
            'reserved_order_id'
        );
        $this->quote->setCustomerId(1);
        $this->quoteResource->save($this->quote);
        /** @var CartItemInterface $cartItem */

        $headerMap = $this->getHeaderMap();

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          customer_address_id: 1
          cart_items: [{ cart_item_id: 100500, quantity: 1 }]
        },
        {
          customer_address_id: 2
          cart_items: [{ cart_item_id: 100501, quantity: 1 }]
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        firstname
        lastname
        company
        street
        city
        postcode
        telephone
      }
    }
  }
}
QUERY;
        self::expectExceptionMessage('No such item added to cart with id 100500');
        $this->graphQlQuery($query, [], '', $headerMap);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_simple_product_saved.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Customer/_files/customer_two_addresses.php
     */
    public function testSetRegisteredCustomerShippingAddressesOnCartWithNoItems()
    {
        $this->quoteResource->load(
            $this->quote,
            'test_order_with_simple_product_without_address',
            'reserved_order_id'
        );
        $maskedQuoteId = $this->quoteIdToMaskedId->execute((int)$this->quote->getId());
        $this->quoteResource->load(
            $this->quote,
            'test_order_with_simple_product_without_address',
            'reserved_order_id'
        );
        $this->quote->setCustomerId(1);
        $this->quoteResource->save($this->quote);

        $headerMap = $this->getHeaderMap();

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          customer_address_id: 2
        },
        {
          customer_address_id: 1
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        firstname
        lastname
        company
        street
        city
        postcode
        telephone
      }
    }
  }
}
QUERY;
        self::expectExceptionMessage('Parameter "cart_items" is required for multishipping');
        $this->graphQlQuery($query, [], '', $headerMap);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_simple_product_saved.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Customer/_files/customer_two_addresses.php
     */
    public function testSetRegisteredCustomerShippingAddressesOnCartWithNoAddressesId()
    {
        $this->quoteResource->load(
            $this->quote,
            'test_order_with_simple_product_without_address',
            'reserved_order_id'
        );
        $maskedQuoteId = $this->quoteIdToMaskedId->execute((int)$this->quote->getId());
        $this->quoteResource->load(
            $this->quote,
            'test_order_with_simple_product_without_address',
            'reserved_order_id'
        );
        $this->quote->setCustomerId(1);
        $this->quoteResource->save($this->quote);
        /** @var CartItemInterface $cartItem */
        $cartItemsCollection = $this->quote->getItemsCollection();
        $cartItem = current($cartItemsCollection->getItems());
        $cartItemId = $cartItem->getItemId();

        $headerMap = $this->getHeaderMap();

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          cart_items: [{ cart_item_id: $cartItemId, quantity: 1 }]
        },
        {
          cart_items: [{ cart_item_id: $cartItemId, quantity: 1 }]
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        firstname
        lastname
        company
        street
        city
        postcode
        telephone
      }
    }
  }
}
QUERY;
        self::expectExceptionMessage('Parameter "customer_address_id" is required for multishipping');
        $this->graphQlQuery($query, [], '', $headerMap);
    }

    /**
     * Verify the all the whitelisted fields for a Address Object
     *
     * @param array $shippingAddressResponse
     * @param int $cartItemId
     */
    private function assertFirstShippingAddressFields(array $shippingAddressResponse, int $cartItemId): void
    {
        $assertionMap = [
            ['response_field' => 'firstname', 'expected_value' => 'John'],
            ['response_field' => 'lastname', 'expected_value' => 'Smith'],
            ['response_field' => 'company', 'expected_value' => 'CompanyName'],
            ['response_field' => 'street', 'expected_value' => [0 => 'Green str, 67']],
            ['response_field' => 'city', 'expected_value' => 'CityM'],
            ['response_field' => 'postcode', 'expected_value' => '75477'],
            ['response_field' => 'telephone', 'expected_value' => '3468676'],
            ['response_field' => 'cart_items', 'expected_value' => [[
                'cart_item_id' => $cartItemId,
                'quantity' => 1,
            ]]]
        ];

        $this->assertResponseFields($shippingAddressResponse, $assertionMap);
    }

    /**
     * Verify the all the whitelisted fields for a Address Object
     *
     * @param array $shippingAddressResponse
     * @param int $cartItemId
     */
    private function assertSecondShippingAddressFields(array $shippingAddressResponse, int $cartItemId): void
    {
        $assertionMap = [
            ['response_field' => 'firstname', 'expected_value' => 'John'],
            ['response_field' => 'lastname', 'expected_value' => 'Smith'],
            ['response_field' => 'street', 'expected_value' => [0 => 'Black str, 48']],
            ['response_field' => 'city', 'expected_value' => 'CityX'],
            ['response_field' => 'postcode', 'expected_value' => '47676'],
            ['response_field' => 'telephone', 'expected_value' => '3234676'],
            ['response_field' => 'cart_items', 'expected_value' => [[
                'cart_item_id' => $cartItemId,
                'quantity' => 2,
            ]]]
        ];

        $this->assertResponseFields($shippingAddressResponse, $assertionMap);
    }

    /**
     * @param string $username
     * @return array
     */
    private function getHeaderMap(string $username = 'customer@example.com'): array
    {
        $password = 'password';
        /** @var CustomerTokenServiceInterface $customerTokenService */
        $customerTokenService = ObjectManager::getInstance()
            ->get(CustomerTokenServiceInterface::class);
        $customerToken = $customerTokenService->createCustomerAccessToken($username, $password);
        $headerMap = ['Authorization' => 'Bearer ' . $customerToken];
        return $headerMap;
    }
}

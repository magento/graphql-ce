<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Quote\Guest;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Multishipping\Helper\Data;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\ObjectManager;

/**
 * Test for set shipping addresses on cart mutation
 */
class SetShippingAddressOnCartTest extends GraphQlAbstract
{
    /**
     * @var QuoteResource
     */
    private $quoteResource;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedId;

    protected function setUp()
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->quoteResource = $objectManager->get(QuoteResource::class);
        $this->quoteFactory = $objectManager->get(QuoteFactory::class);
        $this->quoteIdToMaskedId = $objectManager->get(QuoteIdToMaskedQuoteIdInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_simple_product_saved.php
     */
    public function testSetNewShippingAddress()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReversedQuoteId('test_order_with_simple_product_without_address');

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          address: {
            firstname: "test firstname"
            lastname: "test lastname"
            company: "test company"
            street: ["test street 1", "test street 2"]
            city: "test city"
            region: "test region"
            postcode: "887766"
            country_code: "US"
            telephone: "88776655"
            save_in_address_book: false
          }
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
        country {
          code
          label
        }
        address_type
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        self::assertArrayHasKey('cart', $response['setShippingAddressesOnCart']);
        $cartResponse = $response['setShippingAddressesOnCart']['cart'];
        self::assertArrayHasKey('shipping_addresses', $cartResponse);
        $shippingAddressResponse = current($cartResponse['shipping_addresses']);
        $this->assertNewShippingAddressFields($shippingAddressResponse);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_virtual_product_saved.php
     * @expectedException \Exception
     * @expectedExceptionMessage The Cart includes virtual product(s) only, so a shipping address is not used.
     */
    public function testSetNewShippingAddressOnQuoteWithVirtualProducts()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReversedQuoteId('test_order_with_virtual_product_without_address');

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          address: {
            firstname: "test firstname"
            lastname: "test lastname"
            company: "test company"
            street: ["test street 1", "test street 2"]
            city: "test city"
            region: "test region"
            postcode: "887766"
            country_code: "US"
            telephone: "88776655"
            save_in_address_book: false
          }
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        city
      }
    }
  }
}
QUERY;
        $this->graphQlQuery($query);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_simple_product_saved.php
     * @expectedException \Exception
     * @expectedExceptionMessage The current customer isn't authorized.
     */
    public function testSetShippingAddressFromAddressBook()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReversedQuoteId('test_order_with_simple_product_without_address');

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          customer_address_id: 1
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        city
      }
    }
  }
}
QUERY;
        $this->graphQlQuery($query);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_simple_product_saved.php
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @expectedException \Exception
     */
    public function testSetShippingAddressToCustomerCart()
    {
        $maskedQuoteId = $this->assignQuoteToCustomer('test_order_with_simple_product_without_address', 1);

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          customer_address_id: 1
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        postcode
      }
    }
  }
}
QUERY;
        $this->expectExceptionMessage(
            "The current user cannot perform operations on cart \"$maskedQuoteId\""
        );

        $this->graphQlQuery($query);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_simple_product_saved.php
     * @dataProvider dataProviderUpdateWithMissedRequiredParameters
     * @param string $input
     * @param string $message
     * @throws \Exception
     */
    public function testSetNewShippingAddressWithMissedRequiredParameters(string $input, string $message)
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReversedQuoteId('test_order_with_simple_product_without_address');

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "{$maskedQuoteId}"
      shipping_addresses: [
        {
          {$input}
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        city
      }
    }
  }
}
QUERY;
        $this->expectExceptionMessage($message);
        $this->graphQlQuery($query);
    }

    /**
     * @return array
     */
    public function dataProviderUpdateWithMissedRequiredParameters()
    {
        return [
            'shipping_addresses' => [
                '',
                'The shipping address must contain either "customer_address_id" or "address".',
            ],
            'missed_city' => [
                'address: { save_in_address_book: false }',
                'Field CartAddressInput.city of required type String! was not provided'
            ]
        ];
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/quote_with_simple_product_saved.php
     * @expectedException \Exception
     * @expectedExceptionMessage You cannot specify multiple shipping addresses.
     */
    public function testSetMultipleNewShippingAddresses()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReversedQuoteId('test_order_with_simple_product_without_address');

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          address: {
            firstname: "test firstname"
            lastname: "test lastname"
            company: "test company"
            street: ["test street 1", "test street 2"]
            city: "test city"
            region: "test region"
            postcode: "887766"
            country_code: "US"
            telephone: "88776655"
            save_in_address_book: false
          }
        },
        {
          address: {
            firstname: "test firstname 2"
            lastname: "test lastname 2"
            company: "test company 2"
            street: ["test street 1", "test street 2"]
            city: "test city"
            region: "test region"
            postcode: "887766"
            country_code: "US"
            telephone: "88776655"
            save_in_address_book: false
          }
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        city
      }
    }
  }
}
QUERY;
        $this->graphQlQuery($query);
    }

    /**
     * Verify the all the whitelisted fields for a New Address Object
     *
     * @param array $shippingAddressResponse
     */
    private function assertNewShippingAddressFields(array $shippingAddressResponse): void
    {
        $assertionMap = [
            ['response_field' => 'firstname', 'expected_value' => 'test firstname'],
            ['response_field' => 'lastname', 'expected_value' => 'test lastname'],
            ['response_field' => 'company', 'expected_value' => 'test company'],
            ['response_field' => 'street', 'expected_value' => [0 => 'test street 1', 1 => 'test street 2']],
            ['response_field' => 'city', 'expected_value' => 'test city'],
            ['response_field' => 'postcode', 'expected_value' => '887766'],
            ['response_field' => 'telephone', 'expected_value' => '88776655'],
            ['response_field' => 'country', 'expected_value' => ['code' => 'US', 'label' => 'US']],
            ['response_field' => 'address_type', 'expected_value' => 'SHIPPING']
        ];

        $this->assertResponseFields($shippingAddressResponse, $assertionMap);
    }

    /**
     * @param string $reversedQuoteId
     * @return string
     */
    private function getMaskedQuoteIdByReversedQuoteId(string $reversedQuoteId): string
    {
        $quote = $this->quoteFactory->create();
        $this->quoteResource->load($quote, $reversedQuoteId, 'reserved_order_id');

        return $this->quoteIdToMaskedId->execute((int)$quote->getId());
    }

    /**
     * @param string $reversedQuoteId
     * @param int $customerId
     * @return string
     */
    private function assignQuoteToCustomer(
        string $reversedQuoteId = 'test_order_with_simple_product_without_address',
        int $customerId = 1
    ): string {
        $quote = $this->quoteFactory->create();
        $this->quoteResource->load($quote, $reversedQuoteId, 'reserved_order_id');
        $quote->setCustomerId($customerId);
        $this->quoteResource->save($quote);
        return $this->quoteIdToMaskedId->execute((int)$quote->getId());
    }
}

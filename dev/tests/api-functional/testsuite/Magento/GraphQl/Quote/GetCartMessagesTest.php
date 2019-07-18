<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Quote;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;

class GetCartMessagesTestTest extends GraphQlAbstract
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

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->quoteResource = $objectManager->get(QuoteResource::class);
        $this->quoteFactory = $objectManager->get(QuoteFactory::class);
        $this->quoteIdToMaskedId = $objectManager->get(QuoteIdToMaskedQuoteIdInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/products.php
     * @magentoApiDataFixture Magento/Checkout/_files/active_quote.php
     */
    public function testGetCartMessages()
    {
        $sku = 'simple';
        $qty = 1;
        $maskedQuoteId = $this->getMaskedQuoteId();

        $queryAddProduct = $this->getAddSimpleProductQuery($maskedQuoteId, $sku, $qty);
        $this->graphQlQuery($queryAddProduct);

        $product = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\Product::class);
        $productId = $product->getIdBySku($sku);
        $product->load($productId);
        $product->setStockData(['is_in_stock' => 0]);
        $product->save();

        $queryGetMessages = $this->getCartMessagesQuery($maskedQuoteId);
        $response = $this->graphQlQuery($queryGetMessages);
        self::assertEquals('Some of the products are out of stock.', $response['getCartMessages']['messages'][0]);
    }
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Required parameter "cart_id" is missing.
     */
    public function testRequiredParamMissing()
    {
        $maskedQuoteId = '';

        $query = $this->getCartMessagesQuery($maskedQuoteId);
        $this->graphQlQuery($query);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Requested cart hasn't errors.
     */
    public function testCartWithoutErrors()
    {
        $maskedQuoteId = $this->getMaskedQuoteId();

        $query = $this->getCartMessagesQuery($maskedQuoteId);
        $this->graphQlQuery($query);
    }

    /**
     * @return string
     */
    public function getMaskedQuoteId() : string
    {
        $quote = $this->quoteFactory->create();
        $this->quoteResource->load($quote, 'test_order_1', 'reserved_order_id');

        return $this->quoteIdToMaskedId->execute((int)$quote->getId());
    }

    /**
     * @param string $maskedQuoteId
     * @return string
     */
    public function getCartMessagesQuery(string $maskedQuoteId): string
    {
        return <<<QUERY
mutation {  
  getCartMessages(
    input: {
      cart_id: "{$maskedQuoteId}"
    }
  ){
      messages
  }
}  
QUERY;
    }

    /**
     * @param string $maskedQuoteId
     * @param string $sku
     * @param int $qty
     * @return string
     */
    public function getAddSimpleProductQuery(string $maskedQuoteId, string $sku, int $qty) : string
    {
        return <<<QUERY
mutation {  
  addSimpleProductsToCart(
    input: {
      cart_id: "{$maskedQuoteId}", 
      cartItems: [
        {
          data: {
            qty: $qty
            sku: "$sku"
          }
        }
      ]
    }
  ) {
    cart {
      items {
        qty
      }
    }
  }
}
QUERY;
    }
}

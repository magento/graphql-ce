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
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;

/**
 * Class GetCartMessagesTest
 *
 * @package Magento\GraphQl\Quote
 */
class GetCartMessagesTest extends GraphQlAbstract
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
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->quoteResource = $objectManager->get(QuoteResource::class);
        $this->quoteFactory = $objectManager->get(QuoteFactory::class);
        $this->quoteIdToMaskedId = $objectManager->get(QuoteIdToMaskedQuoteIdInterface::class);
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
    }

    /**
     * @magentoApiDataFixture Magento/Checkout/_files/active_quote.php
     * @expectedException \Exception
     * @expectedExceptionMessage GraphQL response contains errors: Requested cart hasn't errors.
     */
    public function testCartWithoutErrors()
    {
        $maskedQuoteId = $this->getMaskedQuoteId();

        $query = $this->getCartMessagesQuery($maskedQuoteId);
        $this->graphQlMutation($query);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Catalog/_files/set_simple_product_out_of_stock.php
     * @expectedException \Exception
     * @expectedExceptionMessage GraphQL response contains errors: Some of the products are out of stock.
     */
    public function testCartWithOutOfStockProduct()
    {
        $reservedOrderId = 'test_quote';
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute($reservedOrderId);

        $query = $this->getCartMessagesQuery($maskedQuoteId);

        $this->graphQlMutation($query);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
{
    cart(cart_id:"{$maskedQuoteId}"){
    messages
  }
} 
QUERY;
    }
}

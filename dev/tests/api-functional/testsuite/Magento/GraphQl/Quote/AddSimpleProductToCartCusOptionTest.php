<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Quote;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Robots\Model\Config\Value;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\TestFramework\ObjectManager;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Test for add product with custom option cart
 */
class AddSimpleProductToCartCusOptionTest extends GraphQlAbstract
{
    /**
     * @var Bootstrap
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_with_not_req_custom_options.php
     * @magentoAppIsolation enabled
     */
    public function testAddSimpleProductWithMultiCOCheckbox()
    {
        $sku = "simple_not_req_custom_option_2";
        $quote = $this->getQuote();
        $quoteIdToMaskedId = $this->objectManager->create(QuoteIdToMaskedQuoteIdInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($sku, false, null, true);
        $selectedDetails = $this->getSelectedOptionDetails($product, ["checkbox" => 2]);
        $selectedJsonString = $selectedDetails["jsonString"];
        $checkWithResponse = $selectedDetails["checkWithResponse"];
        $maskedQuoteId = $quoteIdToMaskedId->execute((int)$quote->getId());
        $query = $this->prepareAddProductRequestQuery($maskedQuoteId, $sku, $selectedJsonString);
        $response = $this->graphQlQuery($query);
        self::assertArrayHasKey("addSimpleProductsToCart", $response);
        $responseOptionDetails = $this->processResponseForCustomOption($response);
        self::assertTrue(count(array_diff_key($responseOptionDetails, $checkWithResponse["checkbox"])) === 0);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_with_not_req_custom_options.php
     * @magentoAppIsolation enabled
     */
    public function testAddSimpleProductWithOneCOCheckbox()
    {
        $sku = "simple_not_req_custom_option_2";
        $quote = $this->getQuote();
        $quoteIdToMaskedId = $this->objectManager->create(QuoteIdToMaskedQuoteIdInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($sku, false, null, true);
        $selectedDetails = $this->getSelectedOptionDetails($product, ["checkbox" => 1]);
        $selectedJsonString = $selectedDetails["jsonString"];
        $checkWithResponse = $selectedDetails["checkWithResponse"];
        $maskedQuoteId = $quoteIdToMaskedId->execute((int)$quote->getId());
        $query = $this->prepareAddProductRequestQuery($maskedQuoteId, $sku, $selectedJsonString);
        $response = $this->graphQlQuery($query);
        self::assertArrayHasKey("addSimpleProductsToCart", $response);
        $responseOptionDetails = $this->processResponseForCustomOption($response);
        self::assertTrue(count(array_diff_key($responseOptionDetails, $checkWithResponse["checkbox"])) === 0);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_with_not_req_custom_options.php
     * @magentoAppIsolation enabled
     */
    public function testAddSimpleProductWithCOCheckboxDropDown()
    {
        $sku = "simple_not_req_custom_option_2";
        $quote = $this->getQuote();
        $quoteIdToMaskedId = $this->objectManager->create(QuoteIdToMaskedQuoteIdInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($sku, false, null, true);
        $selectedDetails = $this->getSelectedOptionDetails($product, ["checkbox" => 2,"drop_down" => 1]);
        $selectedJsonString = $selectedDetails["jsonString"];
        $checkWithResponse = $selectedDetails["checkWithResponse"];
        $maskedQuoteId = $quoteIdToMaskedId->execute((int)$quote->getId());
        $query = $this->prepareAddProductRequestQuery($maskedQuoteId, $sku, $selectedJsonString);
        $response = $this->graphQlQuery($query);
        self::assertArrayHasKey("addSimpleProductsToCart", $response);
        $responseOptionDetails = $this->processResponseForCustomOption($response);
        self::assertTrue(count(array_diff_key($responseOptionDetails, $checkWithResponse["checkbox_drop_down"])) === 0);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_with_not_req_custom_options.php
     * @magentoAppIsolation enabled
     */
    public function testAddSimpleProductWithCOCheckboxDropDownRadio()
    {
        $sku = "simple_not_req_custom_option_2";
        $quote = $this->getQuote();
        $quoteIdToMaskedId = $this->objectManager->create(QuoteIdToMaskedQuoteIdInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($sku, false, null, true);
        $selectedDetails = $this->getSelectedOptionDetails($product, ["checkbox" => 2,"drop_down" => 1, "radio" => 1]);
        $selectedJsonString = $selectedDetails["jsonString"];
        $checkWithResponse = $selectedDetails["checkWithResponse"];
        $maskedQuoteId = $quoteIdToMaskedId->execute((int)$quote->getId());
        $query = $this->prepareAddProductRequestQuery($maskedQuoteId, $sku, $selectedJsonString);
        $response = $this->graphQlQuery($query);
        self::assertArrayHasKey("addSimpleProductsToCart", $response);
        $responseOptionDetails = $this->processResponseForCustomOption($response);
        self::assertTrue(count(array_diff_key($responseOptionDetails, $checkWithResponse["checkbox_drop_down"])) === 0);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param array $selectedTypes
     * @return array
     */
    public function getSelectedOptionDetails(\Magento\Catalog\Model\Product $product, array $selectedTypes): array
    {
        $optionIds = $this->getOptionDetails($product);
        $checkWithResponse = $selectedOption = [];
        $keyString = implode("_", array_keys($selectedTypes));
        foreach ($selectedTypes as $type => $selectCount) {
            foreach ($optionIds[$type] as $optId => $optVArr) {
                $count = 0;
                foreach ($optVArr as $optVId => $optString) {
                    if ($count < $selectCount) {
                        $selectedOption[] = $optString;
                        $checkWithResponse[$keyString][$optId][] = $optVId;
                        $count++;
                    }
                }
            }
        }

        return [
           "jsonString" => implode(",", $selectedOption),
           "checkWithResponse" => $checkWithResponse
        ];
    }

    /**
     * @param $response
     * @return array
     */
    public function processResponseForCustomOption($response): array
    {
        $responseOptionDetails = [];
        if (isset($response["addSimpleProductsToCart"]["cart"]["items"])) {
            foreach ($response["addSimpleProductsToCart"]["cart"]["items"] as $itemCustomOption) {
                if (isset($itemCustomOption["customizable_options"])) {
                    foreach ($itemCustomOption["customizable_options"] as $option) {
                        foreach ($option["values"] as $optionV) {
                            $responseOptionDetails[$option["id"]][] = $optionV["value"];
                        }
                    }
                }
            }
        }

        return $responseOptionDetails;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getOptionDetails(\Magento\Catalog\Model\Product $product): array
    {
        $customOptions = $product->getOptions();
        $optionIds = [];
        foreach ($customOptions as $option) {
            /** @var \Magento\Catalog\Model\Product\Option $values */
            $values = $option->getValues();
            if (is_array($values)) {
                /** @var \Magento\Catalog\Model\Product\Option\Value $optionValues */
                foreach ($values as $optionValues) {
                    $optV = $optionValues->getOptionTypeId();
                    $optId = $option->getOptionId();
                    $optionString = '{id: ' . $optId . ", value: \"" . $optV . "\"}";
                    $optionIds[$option->getType()][$optId][$optV] = $optionString;
                }
            }
        }

        return $optionIds;
    }

    /**
     * @return Quote
     * @throws \Exception
     */
    public function getQuote(): Quote
    {
        $quote = $this->objectManager->create(Quote::class);
        $quote->setStoreId(1)
            ->setIsActive(true)
            ->setIsMultiShipping(false);

        $this->objectManager->get(\Magento\Quote\Model\QuoteRepository::class)->save($quote);

        /** @var \Magento\Quote\Model\QuoteIdMask $quoteIdMask */
        $quoteIdMask = $this->objectManager
            ->create(\Magento\Quote\Model\QuoteIdMaskFactory::class)
            ->create();

        $quoteIdMask->setQuoteId($quote->getId());
        $quoteIdMask->setDataChanges(true);
        $quoteIdMask->save();
        return $quote;
    }

    /**
     * @param string $maskedQuoteId
     * @param $sku
     * @param $selectedOption
     * @return string
     */
    private function prepareAddProductRequestQuery(string $maskedQuoteId, $sku, $selectedOption): string
    {
        return <<<QUERY
                mutation {
                          addSimpleProductsToCart(input: {
                                cart_id: "{$maskedQuoteId}",
                                cartItems: {
                                    data: {
                                            sku: "{$sku}",
                                            qty: 1
                                    },
                                    customizable_options: [
                                      {$selectedOption}
                                    ]
                              }
                        })
                        { 
                            cart {
                              items {
                                id
                                qty
                                ... on SimpleCartItem {
                                  customizable_options {
                                    id
                                    label
                                    type
                                    is_required
                                    values {
                                      id
                                      label
                                      value
                                      price {
                                        type
                                        units
                                        value
                                      }
                                    }
                                  }
                                }
                                product {
                                  sku
                                }
                              }
                            }    
                          }
                        }
QUERY;
    }

}

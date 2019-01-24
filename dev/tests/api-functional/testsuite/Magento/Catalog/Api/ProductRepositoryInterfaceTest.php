<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Downloadable\Model\Link;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\Store\Model\WebsiteRepository;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception as HTTPExceptionCodes;

/**
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductRepositoryInterfaceTest extends WebapiAbstract
{
    const SERVICE_NAME = 'catalogProductRepositoryV1';
    const SERVICE_VERSION = 'V1';
    const RESOURCE_PATH = '/V1/products';

    const KEY_TIER_PRICES = 'tier_prices';
    const KEY_SPECIAL_PRICE = 'special_price';
    const KEY_CATEGORY_LINKS = 'category_links';

    /**
     * @var array
     */
    private $productData = [
        [
            ProductInterface::SKU => 'simple',
            ProductInterface::NAME => 'Simple Related Product',
            ProductInterface::TYPE_ID => 'simple',
            ProductInterface::PRICE => 10,
        ],
        [
            ProductInterface::SKU => 'simple_with_cross',
            ProductInterface::NAME => 'Simple Product With Related Product',
            ProductInterface::TYPE_ID => 'simple',
            ProductInterface::PRICE => 10
        ],
    ];

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/products_related.php
     */
    public function testGet()
    {
        $productData = $this->productData[0];
        $response = $this->getProduct($productData[ProductInterface::SKU]);
        foreach ([ProductInterface::SKU, ProductInterface::NAME, ProductInterface::PRICE] as $key) {
            $this->assertEquals($productData[$key], $response[$key]);
        }
        $this->assertEquals([1], $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY]["website_ids"]);
    }

    /**
     * @param string $sku
     * @param string|null $storeCode
     * @return array|bool|float|int|string
     */
    protected function getProduct($sku, $storeCode = null)
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $sku,
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Get',
            ],
        ];

        $response = $this->_webApiCall($serviceInfo, ['sku' => $sku], null, $storeCode);
        return $response;
    }

    public function testGetNoSuchEntityException()
    {
        $invalidSku = '(nonExistingSku)';
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $invalidSku,
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Get',
            ],
        ];

        $expectedMessage = "The product that was requested doesn't exist. Verify the product and try again.";

        try {
            $this->_webApiCall($serviceInfo, ['sku' => $invalidSku]);
            $this->fail("Expected throwing exception");
        } catch (\SoapFault $e) {
            $this->assertContains(
                $expectedMessage,
                $e->getMessage(),
                "SoapFault does not contain expected message."
            );
        } catch (\Exception $e) {
            $errorObj = $this->processRestExceptionResult($e);
            $this->assertEquals($expectedMessage, $errorObj['message']);
            $this->assertEquals(HTTPExceptionCodes::HTTP_NOT_FOUND, $e->getCode());
        }
    }

    /**
     * @return array
     */
    public function productCreationProvider()
    {
        $productBuilder = function ($data) {
            return array_replace_recursive(
                $this->getSimpleProductData(),
                $data
            );
        };
        return [
            [$productBuilder([ProductInterface::TYPE_ID => 'simple', ProductInterface::SKU => 'psku-test-1'])],
            [$productBuilder([ProductInterface::TYPE_ID => 'virtual', ProductInterface::SKU => 'psku-test-2'])],
        ];
    }

    /**
     * Load website by website code
     *
     * @param $websiteCode
     * @return Website
     */
    private function loadWebsiteByCode($websiteCode)
    {
        $websiteRepository = Bootstrap::getObjectManager()->get(WebsiteRepository::class);
        try {
            $website = $websiteRepository->get($websiteCode);
        } catch (NoSuchEntityException $e) {
            $this->fail("Couldn`t load website: {$websiteCode}");
        }

        return $website;
    }

    /**
     * Test removing association between product and website 1
     * @magentoApiDataFixture Magento/Catalog/_files/product_with_two_websites.php
     */
    public function testUpdateWithDeleteWebsites()
    {
        $productBuilder[ProductInterface::SKU] = 'unique-simple-azaza';
        /** @var Website $website */
        $website = $this->loadWebsiteByCode('second_website');

        $websitesData = [
            'website_ids' => [
                $website->getId(),
            ]
        ];
        $productBuilder[ProductInterface::EXTENSION_ATTRIBUTES_KEY] = $websitesData;
        $response = $this->updateProduct($productBuilder);
        $this->assertEquals(
            $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY]["website_ids"],
            $websitesData["website_ids"]
        );
    }

    /**
     * Test removing all website associations
     * @magentoApiDataFixture Magento/Catalog/_files/product_with_two_websites.php
     */
    public function testDeleteAllWebsiteAssociations()
    {
        $productBuilder[ProductInterface::SKU] = 'unique-simple-azaza';

        $websitesData = [
            'website_ids' => []
        ];
        $productBuilder[ProductInterface::EXTENSION_ATTRIBUTES_KEY] = $websitesData;
        $response = $this->updateProduct($productBuilder);
        $this->assertEquals(
            $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY]["website_ids"],
            $websitesData["website_ids"]
        );
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/second_website.php
     */
    public function testCreateWithMultipleWebsites()
    {
        $productBuilder = $this->getSimpleProductData();
        $productBuilder[ProductInterface::SKU] = 'test-test-sku';
        $productBuilder[ProductInterface::TYPE_ID] = 'simple';
        /** @var Website $website */
        $website = $this->loadWebsiteByCode('test_website');

        $websitesData = [
            'website_ids' => [
                1,
                (int) $website->getId(),
            ]
        ];
        $productBuilder[ProductInterface::EXTENSION_ATTRIBUTES_KEY] = $websitesData;
        $response = $this->saveProduct($productBuilder);
        $this->assertEquals(
            $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY]["website_ids"],
            $websitesData["website_ids"]
        );
        $this->deleteProduct($productBuilder[ProductInterface::SKU]);
    }

    /**
     * Add product associated with website that is not associated with default store
     *
     * @magentoApiDataFixture Magento/Store/_files/second_website_with_two_stores.php
     */
    public function testCreateWithNonDefaultStoreWebsite()
    {
        $productBuilder = $this->getSimpleProductData();
        $productBuilder[ProductInterface::SKU] = 'test-sku-second-site-123';
        $productBuilder[ProductInterface::TYPE_ID] = 'simple';
        /** @var Website $website */
        $website = $this->loadWebsiteByCode('test');

        $websitesData = [
            'website_ids' => [
                $website->getId(),
            ]
        ];
        $productBuilder[ProductInterface::EXTENSION_ATTRIBUTES_KEY] = $websitesData;
        $response = $this->saveProduct($productBuilder);
        $this->assertEquals(
            $websitesData["website_ids"],
            $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY]["website_ids"]
        );
        $this->deleteProduct($productBuilder[ProductInterface::SKU]);
    }

    /**
     * Update product to be associated with website that is not associated with default store
     *
     * @magentoApiDataFixture Magento/Catalog/_files/product_with_two_websites.php
     * @magentoApiDataFixture Magento/Store/_files/second_website_with_two_stores.php
     */
    public function testUpdateWithNonDefaultStoreWebsite()
    {
        $productBuilder[ProductInterface::SKU] = 'unique-simple-azaza';
        /** @var Website $website */
        $website = $this->loadWebsiteByCode('test');

        $this->assertNotContains(Store::SCOPE_DEFAULT, $website->getStoreCodes());

        $websitesData = [
            'website_ids' => [
                $website->getId(),
            ]
        ];
        $productBuilder[ProductInterface::EXTENSION_ATTRIBUTES_KEY] = $websitesData;
        $response = $this->updateProduct($productBuilder);
        $this->assertEquals(
            $websitesData["website_ids"],
            $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY]["website_ids"]
        );
    }

    /**
     * Update product without specifying websites
     *
     * @magentoApiDataFixture Magento/Catalog/_files/product_with_two_websites.php
     */
    public function testUpdateWithoutWebsiteIds()
    {
        $productBuilder[ProductInterface::SKU] = 'unique-simple-azaza';
        $originalProduct = $this->getProduct($productBuilder[ProductInterface::SKU]);
        $newName = 'Updated Product';

        $productBuilder[ProductInterface::NAME] = $newName;
        $response = $this->updateProduct($productBuilder);
        $this->assertEquals(
            $newName,
            $response[ProductInterface::NAME]
        );
        $this->assertEquals(
            $originalProduct[ProductInterface::EXTENSION_ATTRIBUTES_KEY]["website_ids"],
            $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY]["website_ids"]
        );
    }

    /**
     * @dataProvider productCreationProvider
     */
    public function testCreate($product)
    {
        $response = $this->saveProduct($product);
        $this->assertArrayHasKey(ProductInterface::SKU, $response);
        $this->deleteProduct($product[ProductInterface::SKU]);
    }

    /**
     * @param array $fixtureProduct
     *
     * @dataProvider productCreationProvider
     * @magentoApiDataFixture Magento/Store/_files/fixture_store_with_catalogsearch_index.php
     */
    public function testCreateAllStoreCode($fixtureProduct)
    {
        $response = $this->saveProduct($fixtureProduct, 'all');
        $this->assertArrayHasKey(ProductInterface::SKU, $response);

        /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
        $storeManager = \Magento\TestFramework\ObjectManager::getInstance()->get(
            \Magento\Store\Model\StoreManagerInterface::class
        );

        foreach ($storeManager->getStores(true) as $store) {
            $code = $store->getCode();
            if ($code === Store::ADMIN_CODE) {
                continue;
            }
            $this->assertArrayHasKey(
                ProductInterface::SKU,
                $this->getProduct($fixtureProduct[ProductInterface::SKU], $code)
            );
        }
        $this->deleteProduct($fixtureProduct[ProductInterface::SKU]);
    }

    /**
     * Test creating product with all store code on single store
     *
     * @param array $fixtureProduct
     * @dataProvider productCreationProvider
     */
    public function testCreateAllStoreCodeForSingleWebsite($fixtureProduct)
    {
        $response = $this->saveProduct($fixtureProduct, 'all');
        $this->assertArrayHasKey(ProductInterface::SKU, $response);

        /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
        $storeManager = \Magento\TestFramework\ObjectManager::getInstance()->get(
            \Magento\Store\Model\StoreManagerInterface::class
        );

        foreach ($storeManager->getStores(true) as $store) {
            $code = $store->getCode();
            if ($code === Store::ADMIN_CODE) {
                continue;
            }
            $this->assertArrayHasKey(
                ProductInterface::SKU,
                $this->getProduct($fixtureProduct[ProductInterface::SKU], $code)
            );
        }
        $this->deleteProduct($fixtureProduct[ProductInterface::SKU]);
    }

    public function testCreateInvalidPriceFormat()
    {
        $this->_markTestAsRestOnly("In case of SOAP type casting is handled by PHP SoapServer, no need to test it");
        $expectedMessage = 'Error occurred during "price" processing. '
            . 'The "invalid_format" value\'s type is invalid. The "float" type was expected. Verify and try again.';

        try {
            $this->saveProduct(['name' => 'simple', 'price' => 'invalid_format', 'sku' => 'simple']);
            $this->fail("Expected exception was not raised");
        } catch (\Exception $e) {
            $errorObj = $this->processRestExceptionResult($e);
            $this->assertEquals($expectedMessage, $errorObj['message']);
            $this->assertEquals(HTTPExceptionCodes::HTTP_BAD_REQUEST, $e->getCode());
        }
    }

    /**
     * @param array $fixtureProduct
     *
     * @dataProvider productCreationProvider
     * @magentoApiDataFixture Magento/Store/_files/fixture_store_with_catalogsearch_index.php
     */
    public function testDeleteAllStoreCode($fixtureProduct)
    {
        $sku = $fixtureProduct[ProductInterface::SKU];
        $this->saveProduct($fixtureProduct);
        $this->expectException('Exception');
        $this->expectExceptionMessage(
            "The product that was requested doesn't exist. Verify the product and try again."
        );

        // Delete all with 'all' store code
        $this->deleteProduct($sku);
        $this->getProduct($sku);
    }

    public function testProductLinks()
    {
        // Create simple product
        $productData = [
            ProductInterface::SKU => "product_simple_500",
            ProductInterface::NAME => "Product Simple 500",
            ProductInterface::VISIBILITY => 4,
            ProductInterface::TYPE_ID => 'simple',
            ProductInterface::PRICE => 100,
            ProductInterface::STATUS => 1,
            ProductInterface::TYPE_ID => 'simple',
            ProductInterface::ATTRIBUTE_SET_ID => 4,
            ProductInterface::EXTENSION_ATTRIBUTES_KEY => [
                'stock_item' => $this->getStockItemData()
            ]
        ];

        $this->saveProduct($productData);

        $productLinkData = [
            "sku" => "product_simple_with_related_500",
            "link_type" => "related",
            "linked_product_sku" => "product_simple_500",
            "linked_product_type" => "simple",
            "position" => 0
        ];
        $productWithRelatedData = [
            ProductInterface::SKU => "product_simple_with_related_500",
            ProductInterface::NAME => "Product Simple with Related 500",
            ProductInterface::VISIBILITY => 4,
            ProductInterface::TYPE_ID => 'simple',
            ProductInterface::PRICE => 100,
            ProductInterface::STATUS => 1,
            ProductInterface::TYPE_ID => 'simple',
            ProductInterface::ATTRIBUTE_SET_ID => 4,
            "product_links" => [$productLinkData]
        ];

        $this->saveProduct($productWithRelatedData);
        $response = $this->getProduct("product_simple_with_related_500");

        $this->assertArrayHasKey('product_links', $response);
        $links = $response['product_links'];
        $this->assertEquals(1, count($links));
        $this->assertEquals($productLinkData, $links[0]);

        // update link information
        $productLinkData = [
            "sku" => "product_simple_with_related_500",
            "link_type" => "upsell",
            "linked_product_sku" => "product_simple_500",
            "linked_product_type" => "simple",
            "position" => 0
        ];
        $productWithUpsellData = [
            ProductInterface::SKU => "product_simple_with_related_500",
            ProductInterface::NAME => "Product Simple with Related 500",
            ProductInterface::VISIBILITY => 4,
            ProductInterface::TYPE_ID => 'simple',
            ProductInterface::PRICE => 100,
            ProductInterface::STATUS => 1,
            ProductInterface::ATTRIBUTE_SET_ID => 4,
            "product_links" => [$productLinkData]
        ];

        $this->saveProduct($productWithUpsellData);
        $response = $this->getProduct("product_simple_with_related_500");

        $this->assertArrayHasKey('product_links', $response);
        $links = $response['product_links'];
        $this->assertEquals(1, count($links));
        $this->assertEquals($productLinkData, $links[0]);

        // Remove link
        $productWithNoLinkData = [
            ProductInterface::SKU => "product_simple_with_related_500",
            ProductInterface::NAME => "Product Simple with Related 500",
            ProductInterface::VISIBILITY => 4,
            ProductInterface::TYPE_ID => 'simple',
            ProductInterface::PRICE => 100,
            ProductInterface::STATUS => 1,
            ProductInterface::ATTRIBUTE_SET_ID => 4,
            "product_links" => []
        ];

        $this->saveProduct($productWithNoLinkData);
        $response = $this->getProduct("product_simple_with_related_500");
        $this->assertArrayHasKey('product_links', $response);
        $links = $response['product_links'];
        $this->assertEquals([], $links);

        $this->deleteProduct("product_simple_500");
        $this->deleteProduct("product_simple_with_related_500");
    }

    /**
     * @param string $productSku
     * @return array
     */
    protected function getOptionsData($productSku)
    {
        return [
            [
                "product_sku" => $productSku,
                "title" => "DropdownOption",
                "type" => "drop_down",
                "sort_order" => 0,
                "is_require" => true,
                "values" => [
                    [
                        "title" => "DropdownOption2_1",
                        "sort_order" => 0,
                        "price" => 3,
                        "price_type" => "fixed",
                    ],
                ],
            ],
            [
                "product_sku" => $productSku,
                "title" => "CheckboxOption",
                "type" => "checkbox",
                "sort_order" => 1,
                "is_require" => false,
                "values" => [
                    [
                        "title" => "CheckBoxValue1",
                        "price" => 5,
                        "price_type" => "fixed",
                        "sort_order" => 1,
                    ],
                ],
            ],
        ];
    }

    public function testProductOptions()
    {
        //Create product with options
        $productData = $this->getSimpleProductData();
        $optionsDataInput = $this->getOptionsData($productData['sku']);
        $productData['options'] = $optionsDataInput;
        $this->saveProduct($productData);
        $response = $this->getProduct($productData[ProductInterface::SKU]);

        $this->assertArrayHasKey('options', $response);
        $options = $response['options'];
        $this->assertEquals(2, count($options));
        $this->assertEquals(1, count($options[0]['values']));
        $this->assertEquals(1, count($options[1]['values']));

        //update the product options, adding a value to option 1, delete an option and create a new option
        $options[0]['values'][] = [
            "title" => "Value2",
            "price" => 6,
            "price_type" => "fixed",
            'sort_order' => 3,
        ];
        $options[1] = [
            "product_sku" => $productData['sku'],
            "title" => "DropdownOption2",
            "type" => "drop_down",
            "sort_order" => 3,
            "is_require" => false,
            "values" => [
                [
                    "title" => "Value3",
                    "price" => 7,
                    "price_type" => "fixed",
                    "sort_order" => 4,
                ],
            ],
        ];
        $response['options'] = $options;
        $response = $this->updateProduct($response);
        $this->assertArrayHasKey('options', $response);
        $options = $response['options'];
        $this->assertEquals(2, count($options));
        $this->assertEquals(2, count($options[0]['values']));
        $this->assertEquals(1, count($options[1]['values']));

        //update product without setting options field, option should not be changed
        unset($response['options']);
        $this->updateProduct($response);
        $response = $this->getProduct($productData[ProductInterface::SKU]);
        $this->assertArrayHasKey('options', $response);
        $options = $response['options'];
        $this->assertEquals(2, count($options));

        //update product with empty options, options should be removed
        $response['options'] = [];
        $response = $this->updateProduct($response);
        $this->assertEmpty($response['options']);

        $this->deleteProduct($productData[ProductInterface::SKU]);
    }

    public function testProductWithMediaGallery()
    {
        $testImagePath = __DIR__ . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'test_image.jpg';
        $encodedImage = base64_encode(file_get_contents($testImagePath));
        //create a product with media gallery
        $filename1 = 'tiny1' . time() . '.jpg';
        $filename2 = 'tiny2' . time() . '.jpeg';
        $productData = $this->getSimpleProductData();
        $productData['media_gallery_entries'] = $this->getMediaGalleryData($filename1, $encodedImage, $filename2);
        $response = $this->saveProduct($productData);
        $this->assertArrayHasKey('media_gallery_entries', $response);
        $mediaGalleryEntries = $response['media_gallery_entries'];
        $this->assertEquals(2, count($mediaGalleryEntries));
        $id = $mediaGalleryEntries[0]['id'];
        foreach ($mediaGalleryEntries as &$entry) {
            unset($entry['id']);
        }
        $expectedValue = [
            [
                'label' => 'tiny1',
                'position' => 1,
                'media_type' => 'image',
                'disabled' => true,
                'types' => [],
                'file' => '/t/i/' . $filename1,
            ],
            [
                'label' => 'tiny2',
                'position' => 2,
                'media_type' => 'image',
                'disabled' => false,
                'types' => ['image', 'small_image'],
                'file' => '/t/i/' . $filename2,
            ],
        ];
        $this->assertEquals($expectedValue, $mediaGalleryEntries);
        //update the product media gallery
        $response['media_gallery_entries'] = [
            [
                'id' => $id,
                'media_type' => 'image',
                'label' => 'tiny1_new_label',
                'position' => 1,
                'disabled' => false,
                'types' => ['image', 'small_image'],
                'file' => '/t/i/' . $filename1,
            ],
        ];
        $response = $this->updateProduct($response);
        $mediaGalleryEntries = $response['media_gallery_entries'];
        $this->assertEquals(1, count($mediaGalleryEntries));
        unset($mediaGalleryEntries[0]['id']);
        $expectedValue = [
            [
                'label' => 'tiny1_new_label',
                'media_type' => 'image',
                'position' => 1,
                'disabled' => false,
                'types' => ['image', 'small_image'],
                'file' => '/t/i/' . $filename1,
            ]
        ];
        $this->assertEquals($expectedValue, $mediaGalleryEntries);
        //don't set the media_gallery_entries field, existing entry should not be touched
        unset($response['media_gallery_entries']);
        $response = $this->updateProduct($response);
        $mediaGalleryEntries = $response['media_gallery_entries'];
        $this->assertEquals(1, count($mediaGalleryEntries));
        unset($mediaGalleryEntries[0]['id']);
        $this->assertEquals($expectedValue, $mediaGalleryEntries);
        //pass empty array, delete all existing media gallery entries
        $response['media_gallery_entries'] = [];
        $response = $this->updateProduct($response);
        $this->assertEquals(true, empty($response['media_gallery_entries']));
        $this->deleteProduct($productData[ProductInterface::SKU]);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testUpdate()
    {
        $productData = [
            ProductInterface::NAME => 'Very Simple Product', //new name
            ProductInterface::SKU => 'simple', //sku from fixture
        ];
        $product = $this->getSimpleProductData($productData);
        $response = $this->updateProduct($product);

        $this->assertArrayHasKey(ProductInterface::SKU, $response);
        $this->assertArrayHasKey(ProductInterface::NAME, $response);
        $this->assertEquals($productData[ProductInterface::NAME], $response[ProductInterface::NAME]);
        $this->assertEquals($productData[ProductInterface::SKU], $response[ProductInterface::SKU]);
    }

    /**
     * Update product with extension attributes.
     *
     * @magentoApiDataFixture Magento/Downloadable/_files/product_downloadable.php
     */
    public function testUpdateWithExtensionAttributes(): void
    {
        $sku = 'downloadable-product';
        $linksKey = 'downloadable_product_links';
        $productData = [
            ProductInterface::NAME => 'Downloadable (updated)',
            ProductInterface::SKU => $sku,
        ];
        $response = $this->updateProduct($productData);

        self::assertArrayHasKey(ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY, $response);
        self::assertArrayHasKey($linksKey, $response[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]);
        self::assertCount(1, $response[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY][$linksKey]);

        $linkData = $response[ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY][$linksKey][0];

        self::assertArrayHasKey(Link::KEY_LINK_URL, $linkData);
        self::assertEquals('http://example.com/downloadable.txt', $linkData[Link::KEY_LINK_URL]);
    }

    /**
     * @param array $product
     * @return array|bool|float|int|string
     */
    protected function updateProduct($product)
    {
        if (isset($product['custom_attributes'])) {
            for ($i=0; $i<sizeof($product['custom_attributes']); $i++) {
                if ($product['custom_attributes'][$i]['attribute_code'] == 'category_ids'
                    && !is_array($product['custom_attributes'][$i]['value'])
                ) {
                    $product['custom_attributes'][$i]['value'] = [""];
                }
            }
        }
        $sku = $product[ProductInterface::SKU];
        if (TESTS_WEB_API_ADAPTER == self::ADAPTER_REST) {
            $product[ProductInterface::SKU] = null;
        }

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $sku,
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_PUT,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Save',
            ],
        ];
        $requestData = ['product' => $product];
        $response = $this->_webApiCall($serviceInfo, $requestData);
        return $response;
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testDelete()
    {
        $response = $this->deleteProduct('simple');
        $this->assertTrue($response);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testGetList()
    {
        $searchCriteria = [
            'searchCriteria' => [
                'filter_groups' => [
                    [
                        'filters' => [
                            [
                                'field' => 'sku',
                                'value' => 'simple',
                                'condition_type' => 'eq',
                            ],
                        ],
                    ],
                ],
                'current_page' => 1,
                'page_size' => 2,
            ],
        ];

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '?' . http_build_query($searchCriteria),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'GetList',
            ],
        ];

        $response = $this->_webApiCall($serviceInfo, $searchCriteria);

        $this->assertArrayHasKey('search_criteria', $response);
        $this->assertArrayHasKey('total_count', $response);
        $this->assertArrayHasKey('items', $response);

        $this->assertEquals($searchCriteria['searchCriteria'], $response['search_criteria']);
        $this->assertTrue($response['total_count'] > 0);
        $this->assertTrue(count($response['items']) > 0);

        $this->assertNotNull($response['items'][0]['sku']);
        $this->assertNotNull($response['items'][0][ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]['website_ids']);
        $this->assertEquals('simple', $response['items'][0]['sku']);

        $index = null;
        foreach ($response['items'][0]['custom_attributes'] as $key => $customAttribute) {
            if ($customAttribute['attribute_code'] == 'category_ids') {
                $index = $key;
                break;
            }
        }
        $this->assertNotNull($index, 'Category information wasn\'t set');

        $expectedResult = (TESTS_WEB_API_ADAPTER == self::ADAPTER_SOAP) ? ['string' => '2'] : ['2'];
        $this->assertEquals($expectedResult, $response['items'][0]['custom_attributes'][$index]['value']);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testGetListWithAdditionalParams()
    {
        $this->_markTestAsRestOnly();
        $searchCriteria = [
            'searchCriteria' => [
                'current_page' => 1,
                'page_size' => 2,
            ],
        ];
        $additionalParams = urlencode('items[id,custom_attributes[description]]');

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '?' . http_build_query($searchCriteria) . '&fields=' .
                    $additionalParams,
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ]
        ];

        $response = $this->_webApiCall($serviceInfo, $searchCriteria);

        $this->assertArrayHasKey('items', $response);
        $this->assertTrue(count($response['items']) > 0);

        $indexDescription = null;
        foreach ($response['items'][0]['custom_attributes'] as $key => $customAttribute) {
            if ($customAttribute['attribute_code'] == 'description') {
                $indexDescription = $key;
            }
        }

        $this->assertNotNull($response['items'][0]['custom_attributes'][$indexDescription]['attribute_code']);
        $this->assertNotNull($response['items'][0]['custom_attributes'][$indexDescription]['value']);
        $this->assertTrue(count($response['items'][0]['custom_attributes']) == 1);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/products_with_websites_and_stores.php
     * @return void
     */
    public function testGetListWithFilteringByWebsite()
    {
        $website = $this->loadWebsiteByCode('test');
        $searchCriteria = [
            'searchCriteria' => [
                'filter_groups' => [
                    [
                        'filters' => [
                            [
                                'field' => 'website_id',
                                'value' => $website->getId(),
                                'condition_type' => 'eq',
                            ],
                        ],
                    ],
                ],
                'current_page' => 1,
                'page_size' => 10,
            ],
        ];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '?' . http_build_query($searchCriteria),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'GetList',
            ],
        ];
        $response = $this->_webApiCall($serviceInfo, $searchCriteria);

        $this->assertArrayHasKey('search_criteria', $response);
        $this->assertArrayHasKey('total_count', $response);
        $this->assertArrayHasKey('items', $response);
        $this->assertTrue(count($response['items']) == 1);
        $this->assertTrue(isset($response['items'][0]['sku']));
        $this->assertEquals('simple-2', $response['items'][0]['sku']);
        $this->assertNotNull($response['items'][0][ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]['website_ids']);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/products_with_websites_and_stores.php
     * @dataProvider testGetListWithFilteringByStoreDataProvider
     *
     * @param array $searchCriteria
     * @param array $skus
     * @param int $expectedProductCount
     * @return void
     */
    public function testGetListWithFilteringByStore(array $searchCriteria, array $skus, $expectedProductCount = null)
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '?' . http_build_query($searchCriteria),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'GetList',
            ],
        ];
        $response = $this->_webApiCall($serviceInfo, $searchCriteria);

        $this->assertArrayHasKey('search_criteria', $response);
        $this->assertArrayHasKey('total_count', $response);
        $this->assertArrayHasKey('items', $response);
        if ($expectedProductCount) {
            $this->assertTrue(count($response['items']) == $expectedProductCount);
        }

        $isResultValid = false;
        foreach ($skus as $sku) {
            foreach ($response['items'] as $item) {
                if ($item['sku'] == $sku) {
                    $isResultValid = true;
                }
            }
            $this->assertTrue($isResultValid);
        }
    }

    public function testGetListWithFilteringByStoreDataProvider()
    {
        return [
            [
                [
                    'searchCriteria' => [
                        'filter_groups' => [
                            [
                                'filters' => [
                                    [
                                        'field' => 'store',
                                        'value' => 'fixture_second_store',
                                        'condition_type' => 'eq',
                                    ],
                                ],
                            ],
                        ],
                        'current_page' => 1,
                        'page_size' => 10,
                    ],
                ],
                ['simple-2'],
                1,
            ],
            [
                [
                    'searchCriteria' => [
                        'filter_groups' => [],
                        'current_page' => 1,
                        'page_size' => 10,
                    ],
                ],
                ['simple-2', 'simple-1'],
                null
            ]
        ];
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/products_for_search.php
     */
    public function testGetListWithMultipleFilterGroupsAndSortingAndPagination()
    {
        /** @var FilterBuilder $filterBuilder */
        $filterBuilder = Bootstrap::getObjectManager()->create(FilterBuilder::class);

        $filter1 = $filterBuilder->setField(ProductInterface::NAME)
            ->setValue('search product 2')
            ->create();
        $filter2 = $filterBuilder->setField(ProductInterface::NAME)
            ->setValue('search product 3')
            ->create();
        $filter3 = $filterBuilder->setField(ProductInterface::NAME)
            ->setValue('search product 4')
            ->create();
        $filter4 = $filterBuilder->setField(ProductInterface::NAME)
            ->setValue('search product 5')
            ->create();
        $filter5 = $filterBuilder->setField(ProductInterface::PRICE)
            ->setValue(35)
            ->setConditionType('lt')
            ->create();
        $filter6 = $filterBuilder->setField('category_id')
            ->setValue(333)
            ->create();

        /**@var SortOrderBuilder $sortOrderBuilder */
        $sortOrderBuilder = Bootstrap::getObjectManager()->create(SortOrderBuilder::class);

        /** @var SortOrder $sortOrder */
        $sortOrder = $sortOrderBuilder->setField('meta_title')->setDirection(SortOrder::SORT_DESC)->create();

        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder =  Bootstrap::getObjectManager()->create(SearchCriteriaBuilder::class);

        $searchCriteriaBuilder->addFilters([$filter1, $filter2, $filter3, $filter4]);
        $searchCriteriaBuilder->addFilters([$filter5]);
        $searchCriteriaBuilder->addFilters([$filter6]);
        $searchCriteriaBuilder->setSortOrders([$sortOrder]);

        $searchCriteriaBuilder->setPageSize(2);
        $searchCriteriaBuilder->setCurrentPage(2);

        $searchData = $searchCriteriaBuilder->create()->__toArray();
        $requestData = ['searchCriteria' => $searchData];
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '?' . http_build_query($requestData),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'GetList',
            ],
        ];

        $searchResult = $this->_webApiCall($serviceInfo, $requestData);

        $this->assertEquals(3, $searchResult['total_count']);
        $this->assertEquals(1, count($searchResult['items']));
        $this->assertEquals('search_product_4', $searchResult['items'][0][ProductInterface::SKU]);
        $this->assertNotNull(
            $searchResult['items'][0][ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY]['website_ids']
        );
    }

    /**
     * @param $customAttributes
     * @return array
     */
    protected function convertCustomAttributesToAssociativeArray($customAttributes)
    {
        $converted = [];
        foreach ($customAttributes as $customAttribute) {
            $converted[$customAttribute['attribute_code']] = $customAttribute['value'];
        }
        return $converted;
    }

    /**
     * @param $data
     * @return array
     */
    protected function convertAssociativeArrayToCustomAttributes($data)
    {
        $customAttributes = [];
        foreach ($data as $attributeCode => $attributeValue) {
            $customAttributes[] = ['attribute_code' => $attributeCode, 'value' => $attributeValue];
        }
        return $customAttributes;
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testEavAttributes()
    {
        $response = $this->getProduct('simple');

        $this->assertNotEmpty($response['custom_attributes']);
        $customAttributesData = $this->convertCustomAttributesToAssociativeArray($response['custom_attributes']);
        $this->assertNotTrue(isset($customAttributesData['name']));
        $this->assertNotTrue(isset($customAttributesData['tier_price']));

        //Set description
        $descriptionValue = "new description";
        $customAttributesData['description'] = $descriptionValue;
        $response['custom_attributes'] = $this->convertAssociativeArrayToCustomAttributes($customAttributesData);

        $response = $this->updateProduct($response);
        $this->assertNotEmpty($response['custom_attributes']);

        $customAttributesData = $this->convertCustomAttributesToAssociativeArray($response['custom_attributes']);
        $this->assertTrue(isset($customAttributesData['description']));
        $this->assertEquals($descriptionValue, $customAttributesData['description']);

        $this->deleteProduct('simple');
    }

    /**
     * Get Simple Product Data
     *
     * @param array $productData
     * @return array
     */
    protected function getSimpleProductData($productData = [])
    {
        return [
            ProductInterface::SKU => isset($productData[ProductInterface::SKU])
                ? $productData[ProductInterface::SKU] : uniqid('sku-', true),
            ProductInterface::NAME => isset($productData[ProductInterface::NAME])
                ? $productData[ProductInterface::NAME] : uniqid('sku-', true),
            ProductInterface::VISIBILITY => 4,
            ProductInterface::TYPE_ID => 'simple',
            ProductInterface::PRICE => 3.62,
            ProductInterface::STATUS => 1,
            ProductInterface::TYPE_ID => 'simple',
            ProductInterface::ATTRIBUTE_SET_ID => 4,
            'custom_attributes' => [
                ['attribute_code' => 'cost', 'value' => ''],
                ['attribute_code' => 'description', 'value' => 'Description'],
            ]
        ];
    }

    /**
     * @param $product
     * @param string|null $storeCode
     * @return mixed
     */
    protected function saveProduct($product, $storeCode = null)
    {
        if (isset($product['custom_attributes'])) {
            for ($i=0; $i<sizeof($product['custom_attributes']); $i++) {
                if ($product['custom_attributes'][$i]['attribute_code'] == 'category_ids'
                    && !is_array($product['custom_attributes'][$i]['value'])
                ) {
                    $product['custom_attributes'][$i]['value'] = [""];
                }
            }
        }
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Save',
            ],
        ];
        $requestData = ['product' => $product];
        return $this->_webApiCall($serviceInfo, $requestData, null, $storeCode);
    }

    /**
     * Delete Product
     *
     * @param string $sku
     * @return boolean
     */
    protected function deleteProduct($sku)
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $sku,
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_DELETE,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'DeleteById',
            ],
        ];

        return (TESTS_WEB_API_ADAPTER == self::ADAPTER_SOAP) ?
            $this->_webApiCall($serviceInfo, ['sku' => $sku]) : $this->_webApiCall($serviceInfo);
    }

    public function testTierPrices()
    {
        // create a product with tier prices
        $custGroup1 = \Magento\Customer\Model\Group::NOT_LOGGED_IN_ID;
        $custGroup2 = \Magento\Customer\Model\Group::CUST_GROUP_ALL;
        $productData = $this->getSimpleProductData();
        $productData[self::KEY_TIER_PRICES] = [
            [
                'customer_group_id' => $custGroup1,
                'value' => 3.14,
                'qty' => 5,
            ],
            [
                'customer_group_id' => $custGroup2,
                'value' => 3.45,
                'qty' => 10,
            ]
        ];
        $this->saveProduct($productData);
        $response = $this->getProduct($productData[ProductInterface::SKU]);

        $this->assertArrayHasKey(self::KEY_TIER_PRICES, $response);
        $tierPrices = $response[self::KEY_TIER_PRICES];
        $this->assertNotNull($tierPrices, "CREATE: expected to have tier prices");
        $this->assertCount(2, $tierPrices, "CREATE: expected to have 2 'tier_prices' objects");
        $this->assertEquals(3.14, $tierPrices[0]['value']);
        $this->assertEquals(5, $tierPrices[0]['qty']);
        $this->assertEquals($custGroup1, $tierPrices[0]['customer_group_id']);
        $this->assertEquals(3.45, $tierPrices[1]['value']);
        $this->assertEquals(10, $tierPrices[1]['qty']);
        $this->assertEquals($custGroup2, $tierPrices[1]['customer_group_id']);

        // update the product's tier prices: update 1st tier price, (delete the 2nd tier price), add a new one
        $custGroup3 = 1;
        $tierPrices[0]['value'] = 3.33;
        $tierPrices[0]['qty'] = 6;
        $tierPrices[1] = [
            'customer_group_id' => $custGroup3,
            'value' => 2.10,
            'qty' => 12,
        ];
        $response[self::KEY_TIER_PRICES] = $tierPrices;
        $response = $this->updateProduct($response);

        $this->assertArrayHasKey(self::KEY_TIER_PRICES, $response);
        $tierPrices = $response[self::KEY_TIER_PRICES];
        $this->assertNotNull($tierPrices, "UPDATE 1: expected to have tier prices");
        $this->assertCount(2, $tierPrices, "UPDATE 1: expected to have 2 'tier_prices' objects");
        $this->assertEquals(3.33, $tierPrices[0]['value']);
        $this->assertEquals(6, $tierPrices[0]['qty']);
        $this->assertEquals($custGroup1, $tierPrices[0]['customer_group_id']);
        $this->assertEquals(2.10, $tierPrices[1]['value']);
        $this->assertEquals(12, $tierPrices[1]['qty']);
        $this->assertEquals($custGroup3, $tierPrices[1]['customer_group_id']);

        // update the product without any mention of tier prices; no change expected for tier pricing
        $response = $this->getProduct($productData[ProductInterface::SKU]);
        unset($response[self::KEY_TIER_PRICES]);
        $response = $this->updateProduct($response);

        $this->assertArrayHasKey(self::KEY_TIER_PRICES, $response);
        $tierPrices = $response[self::KEY_TIER_PRICES];
        $this->assertNotNull($tierPrices, "UPDATE 2: expected to have tier prices");
        $this->assertCount(2, $tierPrices, "UPDATE 2: expected to have 2 'tier_prices' objects");
        $this->assertEquals(3.33, $tierPrices[0]['value']);
        $this->assertEquals(6, $tierPrices[0]['qty']);
        $this->assertEquals($custGroup1, $tierPrices[0]['customer_group_id']);
        $this->assertEquals(2.10, $tierPrices[1]['value']);
        $this->assertEquals(12, $tierPrices[1]['qty']);
        $this->assertEquals($custGroup3, $tierPrices[1]['customer_group_id']);

        // update the product with empty tier prices; expect to have the existing tier prices removed
        $response = $this->getProduct($productData[ProductInterface::SKU]);
        $response[self::KEY_TIER_PRICES] = [];
        $response = $this->updateProduct($response);
        $this->assertArrayHasKey(self::KEY_TIER_PRICES, $response, "expected to have the 'tier_prices' key");
        $this->assertEmpty($response[self::KEY_TIER_PRICES], "expected to have an empty array of 'tier_prices'");

        // delete the product with tier prices; expect that all goes well
        $response = $this->deleteProduct($productData[ProductInterface::SKU]);
        $this->assertTrue($response);
    }

    /**
     * @return array
     */
    private function getStockItemData()
    {
        return [
            StockItemInterface::IS_IN_STOCK => 1,
            StockItemInterface::QTY => 100500,
            StockItemInterface::IS_QTY_DECIMAL => 1,
            StockItemInterface::SHOW_DEFAULT_NOTIFICATION_MESSAGE => 0,
            StockItemInterface::USE_CONFIG_MIN_QTY => 0,
            StockItemInterface::USE_CONFIG_MIN_SALE_QTY => 0,
            StockItemInterface::MIN_QTY => 1,
            StockItemInterface::MIN_SALE_QTY => 1,
            StockItemInterface::MAX_SALE_QTY => 100,
            StockItemInterface::USE_CONFIG_MAX_SALE_QTY => 0,
            StockItemInterface::USE_CONFIG_BACKORDERS => 0,
            StockItemInterface::BACKORDERS => 0,
            StockItemInterface::USE_CONFIG_NOTIFY_STOCK_QTY => 0,
            StockItemInterface::NOTIFY_STOCK_QTY => 0,
            StockItemInterface::USE_CONFIG_QTY_INCREMENTS => 0,
            StockItemInterface::QTY_INCREMENTS => 0,
            StockItemInterface::USE_CONFIG_ENABLE_QTY_INC => 0,
            StockItemInterface::ENABLE_QTY_INCREMENTS => 0,
            StockItemInterface::USE_CONFIG_MANAGE_STOCK => 1,
            StockItemInterface::MANAGE_STOCK => 1,
            StockItemInterface::LOW_STOCK_DATE => null,
            StockItemInterface::IS_DECIMAL_DIVIDED => 0,
            StockItemInterface::STOCK_STATUS_CHANGED_AUTO => 0,
        ];
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/category_product.php
     */
    public function testProductCategoryLinks()
    {
        // Create simple product
        $productData = $this->getSimpleProductData();
        $productData[ProductInterface::EXTENSION_ATTRIBUTES_KEY] = [
            self::KEY_CATEGORY_LINKS => [['category_id' => 333, 'position' => 0]]
        ];
        $response = $this->saveProduct($productData);
        $this->assertEquals(
            [['category_id' => 333, 'position' => 0]],
            $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY][self::KEY_CATEGORY_LINKS]
        );
        $response = $this->getProduct($productData[ProductInterface::SKU]);
        $this->assertArrayHasKey(ProductInterface::EXTENSION_ATTRIBUTES_KEY, $response);
        $extensionAttributes = $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY];
        $this->assertArrayHasKey(self::KEY_CATEGORY_LINKS, $extensionAttributes);
        $this->assertEquals([['category_id' => 333, 'position' => 0]], $extensionAttributes[self::KEY_CATEGORY_LINKS]);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/category_product.php
     */
    public function testUpdateProductCategoryLinksNullOrNotExists()
    {
        $response = $this->getProduct('simple333');
        // update product without category_link or category_link is null
        $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY][self::KEY_CATEGORY_LINKS] = null;
        $response = $this->updateProduct($response);
        $this->assertEquals(
            [['category_id' => 333, 'position' => 0]],
            $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY][self::KEY_CATEGORY_LINKS]
        );
        unset($response[ProductInterface::EXTENSION_ATTRIBUTES_KEY][self::KEY_CATEGORY_LINKS]);
        $response = $this->updateProduct($response);
        $this->assertEquals(
            [['category_id' => 333, 'position' => 0]],
            $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY][self::KEY_CATEGORY_LINKS]
        );
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/category_product.php
     */
    public function testUpdateProductCategoryLinksPosistion()
    {
        $response = $this->getProduct('simple333');
        // update category_link position
        $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY][self::KEY_CATEGORY_LINKS] = [
            ['category_id' => 333, 'position' => 10]
        ];
        $response = $this->updateProduct($response);
        $this->assertEquals(
            [['category_id' => 333, 'position' => 10]],
            $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY][self::KEY_CATEGORY_LINKS]
        );
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/category_product.php
     */
    public function testUpdateProductCategoryLinksUnassign()
    {
        $response = $this->getProduct('simple333');
        // unassign category_links from product
        $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY][self::KEY_CATEGORY_LINKS] = [];
        $response = $this->updateProduct($response);
        $this->assertArrayNotHasKey(self::KEY_CATEGORY_LINKS, $response[ProductInterface::EXTENSION_ATTRIBUTES_KEY]);
    }

    /**
     * @param $filename1
     * @param $encodedImage
     * @param $filename2
     * @return array
     */
    private function getMediaGalleryData($filename1, $encodedImage, $filename2)
    {
        return [
            [
                'position' => 1,
                'media_type' => 'image',
                'disabled' => true,
                'label' => 'tiny1',
                'types' => [],
                'content' => [
                    'type' => 'image/jpeg',
                    'name' => $filename1,
                    'base64_encoded_data' => $encodedImage,
                ]
            ],
            [
                'position' => 2,
                'media_type' => 'image',
                'disabled' => false,
                'label' => 'tiny2',
                'types' => ['image', 'small_image'],
                'content' => [
                    'type' => 'image/jpeg',
                    'name' => $filename2,
                    'base64_encoded_data' => $encodedImage,
                ]
            ],
        ];
    }

    public function testSpecialPrice()
    {
        $productData = $this->getSimpleProductData();
        $productData['custom_attributes'] = [
            ['attribute_code' => self::KEY_SPECIAL_PRICE, 'value' => '1']
        ];
        $this->saveProduct($productData);
        $response = $this->getProduct($productData[ProductInterface::SKU]);
        $customAttributes = $response['custom_attributes'];
        $this->assertNotEmpty($customAttributes);
        $missingAttributes = ['news_from_date', 'custom_design_from'];
        $expectedAttribute = ['special_price', 'special_from_date'];
        $attributeCodes = array_column($customAttributes, 'attribute_code');
        $this->assertEquals(0, count(array_intersect($attributeCodes, $missingAttributes)));
        $this->assertEquals(2, count(array_intersect($attributeCodes, $expectedAttribute)));
    }

    /**
     * Tests the ability to "reset" (nullify) a special_price by passing null in the web api request.
     *
     * Steps:
     *  1. Save the product with a special_price of $5.00
     *  2. Save the product with a special_price of null
     *  3. Confirm that the special_price is no longer set
     */
    public function testResetSpecialPrice()
    {
        $this->_markTestAsRestOnly(
            'In order to properly run this test for SOAP, XML must be used to specify <value></value> ' .
            'for the special_price value. Otherwise, the null value gets processed as a string and ' .
            'cast to a double value of 0.0.'
        );
        $productData = $this->getSimpleProductData();
        $productData['custom_attributes'] = [
            ['attribute_code' => self::KEY_SPECIAL_PRICE, 'value' => 5.00]
        ];
        $this->saveProduct($productData);
        $response = $this->getProduct($productData[ProductInterface::SKU]);
        $customAttributes = array_column($response['custom_attributes'], 'value', 'attribute_code');
        $this->assertEquals(5, $customAttributes[self::KEY_SPECIAL_PRICE]);
        $productData['custom_attributes'] = [
            ['attribute_code' => self::KEY_SPECIAL_PRICE, 'value' => null]
        ];
        $this->saveProduct($productData);
        $response = $this->getProduct($productData[ProductInterface::SKU]);
        $customAttributes = array_column($response['custom_attributes'], 'value', 'attribute_code');
        $this->assertFalse(array_key_exists(self::KEY_SPECIAL_PRICE, $customAttributes));
    }

    public function testUpdateStatus()
    {
        // Create simple product
        $productData = [
            ProductInterface::SKU => "product_simple_502",
            ProductInterface::NAME => "Product Simple 502",
            ProductInterface::VISIBILITY => 4,
            ProductInterface::TYPE_ID => 'simple',
            ProductInterface::PRICE => 100,
            ProductInterface::STATUS => 0,
            ProductInterface::TYPE_ID => 'simple',
            ProductInterface::ATTRIBUTE_SET_ID => 4,
        ];

        // Save product with status disabled
        $this->saveProduct($productData);
        $response = $this->getProduct($productData[ProductInterface::SKU]);
        $this->assertEquals(0, $response['status']);

        // Update the product
        $productData[ProductInterface::PRICE] = 200;
        $this->saveProduct($productData);
        $response = $this->getProduct($productData[ProductInterface::SKU]);

        // Status should still be disabled
        $this->assertEquals(0, $response['status']);
        // Price should be updated
        $this->assertEquals(200, $response['price']);
    }

    /**
     * Test saving product with custom attribute of multiselect type
     *
     * 1. Create multi-select attribute
     * 2. Create product and set 2 options out of 3 to multi-select attribute
     * 3. Verify that 2 options are selected
     * 4. Unselect all options
     * 5. Verify that non options are selected
     *
     * @magentoApiDataFixture Magento/Catalog/_files/multiselect_attribute.php
     */
    public function testUpdateMultiselectAttributes()
    {
        $multiselectAttributeCode = 'multiselect_attribute';
        $multiselectOptions = $this->getAttributeOptions($multiselectAttributeCode);
        $option1 = $multiselectOptions[1]['value'];
        $option2 = $multiselectOptions[2]['value'];

        $productData = $this->getSimpleProductData();
        $productData['custom_attributes'] = [
            ['attribute_code' => $multiselectAttributeCode, 'value' => "{$option1},{$option2}"]
        ];
        $this->saveProduct($productData, 'all');

        $this->assertMultiselectValue(
            $productData[ProductInterface::SKU],
            $multiselectAttributeCode,
            "{$option1},{$option2}"
        );

        $productData['custom_attributes'] = [
            ['attribute_code' => $multiselectAttributeCode, 'value' => ""]
        ];
        $this->saveProduct($productData, 'all');
        $this->assertMultiselectValue(
            $productData[ProductInterface::SKU],
            $multiselectAttributeCode,
            ""
        );
    }

    /**
     * @param string $attributeCode
     * @return array|bool|float|int|string
     */
    private function getAttributeOptions($attributeCode)
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/products/attributes/' . $attributeCode . '/options',
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => 'catalogProductAttributeOptionManagementV1',
                'serviceVersion' => 'V1',
                'operation' => 'catalogProductAttributeOptionManagementV1getItems',
            ],
        ];

        return $this->_webApiCall($serviceInfo, ['attributeCode' => $attributeCode]);
    }

    /**
     * @param string $productSku
     * @param string $multiselectAttributeCode
     * @param string $expectedMultiselectValue
     */
    private function assertMultiselectValue($productSku, $multiselectAttributeCode, $expectedMultiselectValue)
    {
        $response = $this->getProduct($productSku, 'all');
        $customAttributes = $response['custom_attributes'];
        $this->assertNotEmpty($customAttributes);
        $multiselectValue = null;
        foreach ($customAttributes as $customAttribute) {
            if ($customAttribute['attribute_code'] == $multiselectAttributeCode) {
                $multiselectValue = $customAttribute['value'];
                break;
            }
        }
        $this->assertEquals($expectedMultiselectValue, $multiselectValue);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Swatches;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Swatches\Helper\Data as SwatchesHelper;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Class ProductViewTest
 * @package Magento\GraphQl\Swatches
 */
class ProductViewTest extends GraphQlAbstract
{
    public const IMAGE = 'IMAGE';
    public const COLOR = 'COLOR';
    /**
     * @var array
     */
    private $configurableOptions = [];
    /**
     * @var SwatchesHelper
     */
    private $swatchesHelper;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->swatchesHelper = ObjectManager::getInstance()->get(SwatchesHelper::class);
    }

    /**
     * @magentoApiDataFixture Magento/Swatches/_files/configurable_products_with_swatches.php
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testQuerySwatchDataFields()
    {
        $productSku = 'configurable';

        $query
            = <<<QUERY
{
  products(filter: {sku: {eq: "configurable"}}) {
    items {
      name
        ... on ConfigurableProduct{
        configurable_options{
          id
          label
          values {
            value_index
            label
            default_label
            store_label
            use_default_value
            swatch_data {
              type
              value
            }
          }
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($productSku, false, null, true);
        /** @var MetadataPool $metadataPool */
        $metadataPool = ObjectManager::getInstance()->get(MetadataPool::class);
        $product->setId(
            $product->getData($metadataPool->getMetadata(ProductInterface::class)->getLinkField())
        );
        $this->assertArrayHasKey('products', $response);
        $this->assertArrayHasKey('items', $response['products']);
        $this->assertArrayHasKey('swatch_data', $response['products']['items'][0]['configurable_options'][0]['values'][0]);
        $configurableOptions = $this->getConfigurableOptions();

        $this->assertBaseFields($response['products'], $configurableOptions);
    }

    /**
     * @param $actualResponse
     * @param $configurableOptions
     */
    private function assertBaseFields($actualResponse, $configurableOptions): void
    {
        $assertionMap =
            [
                'items' => [
                    [
                        'name' => 'Configurable Product',
                        'configurable_options' => [
                            [
                                'id' => (int)$configurableOptions['id'],
                                'label' => $configurableOptions['label'],
                                'values' => $configurableOptions['options']
                            ],
                        ],
                    ],
                ],
            ];

        $this->assertResponseFields($actualResponse, $assertionMap);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getConfigurableOptions(): array
    {
        if (!empty($this->configurableOptions)) {
            return $this->configurableOptions;
        }
        $productSku = 'configurable';
        /** @var ProductRepositoryInterface $productRepo */
        $productRepo = ObjectManager::getInstance()->get(ProductRepositoryInterface::class);

        $product = $productRepo->get($productSku);
        $configurableAttributeOptions = $product->getExtensionAttributes()->getConfigurableProductOptions();
        $configurableAttributeOptionsData = [];
        foreach ($configurableAttributeOptions as $option) {
            $optionData = $option->getData();
            $configurableAttributeOptionsData['label'] = $optionData['label'];
            $cnt = 0;
            foreach ($optionData['options'] as $data) {
                $configurableAttributeOptionsData['options'][$cnt]['value_index'] = (int)$data['value_index'];
                $configurableAttributeOptionsData['options'][$cnt]['label'] = $data['label'];
                $configurableAttributeOptionsData['options'][$cnt]['default_label'] = $data['default_label'];
                $configurableAttributeOptionsData['options'][$cnt]['store_label'] = $data['store_label'];
                $configurableAttributeOptionsData['options'][$cnt]['use_default_value'] = $data['use_default_value'];
                $cnt++;
            }

            $configurableAttributeOptionsData['id'] = $option->getId();
            $configurableAttributeOptionsData['attribute_code']
                = $option->getProductAttribute()->getAttributeCode();
            $count = 0;
            foreach ($configurableAttributeOptionsData['options'] as $value) {
                $swatchData = array_values($this->swatchesHelper->getSwatchesByOptionsId([$value['value_index']]));
                if ($swatchData[0]['type'] === '1') {
                    $swatchData[0]['type'] = self::COLOR;
                } else {
                    $swatchData[0]['type'] = self::IMAGE;
                }

                $configurableAttributeOptionsData['options'][$count]['swatch_data']['type'] = $swatchData[0]['type'];
                $configurableAttributeOptionsData['options'][$count]['swatch_data']['value'] = $swatchData[0]['value'];
                $count++;
            }
        }

        return $this->configurableOptions = $configurableAttributeOptionsData;
    }
}

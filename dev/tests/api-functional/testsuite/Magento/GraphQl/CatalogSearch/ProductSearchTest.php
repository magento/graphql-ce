<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\CatalogSearch;

use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for catalog search related queries
 */
class ProductSearchTest extends GraphQlAbstract
{
    /**
     * @magentoApiDataFixture Magento/Catalog/_files/products_with_layered_navigation_attribute.php
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSearchWithFilter()
    {
        $query = <<<QUERY
{
  products (
    search: "simple"
    filter: {
      category_id: {
        eq: "2"
      }
    }
  ) {
    items {
      id
      name
      sku
    }
    total_count
  }
}
QUERY;

        $response = $this->graphQlQuery($query);

        $this->assertArrayHasKey('products', $response);
        $this->assertArrayHasKey('total_count', $response['products']);
        $this->assertArrayHasKey('items', $response['products']);
        $this->assertEquals(2, $response['products']['total_count']);

        $items = $response['products']['items'];
        $item = current($items);
        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('name', $item);
        $this->assertArrayHasKey('sku', $item);
    }
}

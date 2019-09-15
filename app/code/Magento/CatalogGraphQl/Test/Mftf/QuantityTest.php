<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogGraphQl\Test\Mftf;

use Exception;
use Magento\TestFramework\TestCase\GraphQlAbstract;

class QuantityTest extends GraphQlAbstract
{

    /**
     * @throws Exception
     */
    public function testQuantityConsistency()
    {
        $query
            = <<<QUERY
{
  products(
    search: "Impulse Duffle"
    pageSize: 10
  )
  {
    total_count
    items {
      name
      sku
      tier_prices{
        value
        quantity
        qty
      }
    }
  }
}
QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertArrayHasKey('products', $response);
        $this->assertArrayHasKey('items', $response['products']);
        $this->assertArrayHasKey('tier_prices', $response['products']['items']);
    }
}

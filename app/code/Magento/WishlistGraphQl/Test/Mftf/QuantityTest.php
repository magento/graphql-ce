<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\WishlistGraphQl\Test\Mftf;

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
  wishlist {
    items {
      id
      qty
      quantity
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

        $response = $this->graphQlQuery($query);
        $this->assertArrayHasKey('wishlist', $response);
        $this->assertArrayHasKey('items', $response['wishlist']);
    }
}

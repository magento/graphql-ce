<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Review;

use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for retrieving product reviews
 */
class ProductReviewsTest extends GraphQlAbstract
{
    /**
     * Product reviews test
     *
     * @magentoApiDataFixture Magento/Review/_files/customer_reviews.php
     */
    public function testGetProductReviews()
    {
        $productId = 1;

        $query = <<<QUERY
query {
  productReviews(entity_id: {$productId}) {
    items {
      review_id
      entity_id
      customer_id
      title
      detail
      nickname
      created_at
      rating_votes {
        rating_id
        rating_code
        percent
        value
      }
    }
  }
}
QUERY;

        $response = $this->graphQlQuery($query, [], '');

        $this->assertNotEmpty($response['productReviews']['items']);
        $this->assertInternalType('array', $response['productReviews']['items']);
        $this->assertCount(3, $response['productReviews']['items']);

        foreach ($this->getExpectedData() as $key => $data) {
            $this->assertEquals($data['entity_id'], $response['productReviews']['items'][$key]['entity_id']);
            $this->assertEquals($data['customer_id'], $response['productReviews']['items'][$key]['customer_id']);
            $this->assertEquals($data['title'], $response['productReviews']['items'][$key]['title']);
            $this->assertEquals($data['detail'], $response['productReviews']['items'][$key]['detail']);
            $this->assertEquals($data['nickname'], $response['productReviews']['items'][$key]['nickname']);

            $this->assertNotEmpty($response['productReviews']['items'][$key]['rating_votes']);
            $this->assertInternalType('array', $response['productReviews']['items'][$key]['rating_votes']);
        }
    }

    /**
     * Get expected data
     *
     * @return array
     */
    private function getExpectedData()
    {
        return [
            [
                'entity_id' => 1,
                'customer_id' => null,
                'title' => 'GraphQl: Approved Empty Customer Review Summary',
                'detail' => 'Review text',
                'nickname' => 'Nickname',
            ],
            [
                'entity_id' => 1,
                'customer_id' => 1,
                'title' => 'GraphQl: Approved Review Summary',
                'detail' => 'Review text',
                'nickname' => 'Nickname',
            ],
            [
                'entity_id' => 1,
                'customer_id' => 1,
                'title' => 'GraphQl: Secondary Approved Review Summary',
                'detail' => 'Review text',
                'nickname' => 'Nickname',
            ],
        ];
    }
}

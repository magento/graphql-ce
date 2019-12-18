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
        $sku = 'simple';

        $query = <<<QUERY
query {
  productReviews(sku: "{$sku}", pageSize: 2, currentPage: 1, sort: {created_at: DESC}) {
    items {
      review_id
      product {
        name
        sku
      }
      title
      review_text
      nickname
      created_at
      average_rating
      ratings {
        name
        percent
        value
      }
    }
    page_info {
      page_size
      current_page
      total_pages
    }
    total_count
  }
}
QUERY;

        $response = $this->graphQlQuery($query, [], '');

        $this->assertNotEmpty($response['productReviews']['items']);
        $this->assertInternalType('array', $response['productReviews']['items']);
        $this->assertEquals(3, $response['productReviews']['total_count']);
        $this->assertEquals(2, $response['productReviews']['page_info']['page_size']);
        $this->assertEquals(1, $response['productReviews']['page_info']['current_page']);
        $this->assertEquals(2, $response['productReviews']['page_info']['total_pages']);

        foreach ($this->getExpectedData() as $key => $data) {
            $this->assertEquals($data['title'], $response['productReviews']['items'][$key]['title']);
            $this->assertEquals($data['review_text'], $response['productReviews']['items'][$key]['review_text']);
            $this->assertEquals($data['nickname'], $response['productReviews']['items'][$key]['nickname']);

            $this->assertEquals($response['productReviews']['items'][$key]['product']['sku'], $sku);
            $this->assertNotEmpty($response['productReviews']['items'][$key]['ratings']);
            $this->assertInternalType('array', $response['productReviews']['items'][$key]['ratings']);
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
                'title' => 'GraphQl: Approved Empty Customer Review Summary',
                'review_text' => 'Review text',
                'nickname' => 'Nickname',
            ],
            [
                'title' => 'GraphQl: Approved Review Summary',
                'review_text' => 'Review text',
                'nickname' => 'Nickname',
            ],
        ];
    }
}

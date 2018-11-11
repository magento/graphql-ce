<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Review;

use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Review\Model\Review;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for retrieving customer product reviews
 */
class CustomerProductReviewsTest extends GraphQlAbstract
{
    /**
     * Customer token service
     *
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
    }

    /**
     * Customer product reviews test
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Review/_files/customer_reviews.php
     */
    public function testGetCustomerProductReviews()
    {
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';

        $query = <<<QUERY
query {
  customerProductReviews(pageSize: 3, currentPage: 1, sort: {created_at: DESC}) {
    items {
      review_id
      product {
        name
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

        $response = $this->graphQlQuery($query, [], '', $this->getCustomerAuthHeaders($currentEmail, $currentPassword));

        $this->assertNotEmpty($response['customerProductReviews']['items']);
        $this->assertInternalType('array', $response['customerProductReviews']['items']);
        $this->assertEquals(4, $response['customerProductReviews']['total_count']);
        $this->assertEquals(3, $response['customerProductReviews']['page_info']['page_size']);
        $this->assertEquals(1, $response['customerProductReviews']['page_info']['current_page']);
        $this->assertEquals(2, $response['customerProductReviews']['page_info']['total_pages']);

        foreach ($this->getExpectedData() as $key => $data) {
            $this->assertEquals($data['product_name'], $response['customerProductReviews']['items'][$key]['product']['name']);
            $this->assertEquals($data['title'], $response['customerProductReviews']['items'][$key]['title']);
            $this->assertEquals($data['review_text'], $response['customerProductReviews']['items'][$key]['review_text']);
            $this->assertEquals($data['nickname'], $response['customerProductReviews']['items'][$key]['nickname']);
        }
    }

    /**
     * Get customer authentication headers
     *
     * @param string $email
     * @param string $password
     * @return array
     */
    private function getCustomerAuthHeaders(string $email, string $password): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($email, $password);
        return ['Authorization' => 'Bearer ' . $customerToken];
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
                'product_name' => 'Simple Product',
                'title' => 'GraphQl: Not Approved Review Summary',
                'review_text' => 'Review text',
                'nickname' => 'Nickname',
            ],
            [
                'product_name' => 'Simple Product',
                'title' => 'GraphQl: Approved Review Summary',
                'review_text' => 'Review text',
                'nickname' => 'Nickname',
            ],
            [
                'product_name' => 'Simple Product',
                'title' => 'GraphQl: Secondary Approved Review Summary',
                'review_text' => 'Review text',
                'nickname' => 'Nickname',
            ],
        ];
    }
}

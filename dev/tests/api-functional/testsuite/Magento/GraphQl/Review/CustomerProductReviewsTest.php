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
     * Customer product reivews test
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
  customerProductReviews {
    items {
      review_id
      entity_id
      store_id
      entity_name
      title
      detail
      sum
      count
      nickname
      status_id
      created_at
    }
  }
}
QUERY;

        $response = $this->graphQlQuery($query, [], '', $this->getCustomerAuthHeaders($currentEmail, $currentPassword));

        $this->assertNotEmpty($response['customerProductReviews']['items']);
        $this->assertInternalType('array', $response['customerProductReviews']['items']);
        $this->assertCount(4, $response['customerProductReviews']['items']);

        foreach ($this->getExpectedData() as $key => $data) {
            $this->assertEquals($data['entity_id'], $response['customerProductReviews']['items'][$key]['entity_id']);
            $this->assertEquals($data['store_id'], $response['customerProductReviews']['items'][$key]['store_id']);
            $this->assertEquals($data['entity_name'], $response['customerProductReviews']['items'][$key]['entity_name']);
            $this->assertEquals($data['title'], $response['customerProductReviews']['items'][$key]['title']);
            $this->assertEquals($data['detail'], $response['customerProductReviews']['items'][$key]['detail']);
            $this->assertEquals($data['status_id'], $response['customerProductReviews']['items'][$key]['status_id']);
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
                'entity_id' => 1,
                'store_id' => 1,
                'entity_name' => 'Simple Product',
                'title' => 'GraphQl: Not Approved Review Summary',
                'detail' => 'Review text',
                'status_id' => Review::STATUS_NOT_APPROVED,
                'nickname' => 'Nickname',
            ],
            [
                'entity_id' => 1,
                'store_id' => 1,
                'entity_name' => 'Simple Product',
                'title' => 'GraphQl: Approved Review Summary',
                'detail' => 'Review text',
                'status_id' => Review::STATUS_APPROVED,
                'nickname' => 'Nickname',
            ],
            [
                'entity_id' => 1,
                'store_id' => 1,
                'entity_name' => 'Simple Product',
                'title' => 'GraphQl: Secondary Approved Review Summary',
                'detail' => 'Review text',
                'status_id' => Review::STATUS_APPROVED,
                'nickname' => 'Nickname',
            ],
            [
                'entity_id' => 1,
                'store_id' => 1,
                'entity_name' => 'Simple Product',
                'title' => 'GraphQl: Pending Review Summary',
                'detail' => 'Review text',
                'status_id' => Review::STATUS_PENDING,
                'nickname' => 'Nickname',
            ],
        ];
    }
}

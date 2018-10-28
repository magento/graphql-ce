<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Review;

use Magento\Review\Model\ResourceModel\Rating\Collection as RatingCollection;
use Magento\Review\Model\ResourceModel\Rating\Option\Collection as RatingOptionCollection;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for creating a product review
 */
class CreateProductReviewsTest extends GraphQlAbstract
{
    /**
     * @var RatingCollection
     */
    private $ratingCollection;

    /**
     * @var RatingOptionCollection
     */
    private $ratingOptionCollection;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->ratingCollection = $objectManager->get(RatingCollection::class);
        $this->ratingOptionCollection = $objectManager->get(RatingOptionCollection::class);
    }

    /**
     * Create product review test
     *
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testCreateProductReviews()
    {
        $entityId = 1;
        $entityType = 'product';
        $nickname = 'Mutation';
        $title = 'Mutation Review Title';
        $detail = 'Mutation Review Detail';

        /** @var \Magento\Review\Model\Rating $rating */
        $rating = $this->ratingCollection
            ->setPageSize(1)
            ->setCurPage(4)
            ->getFirstItem();

        /** @var \Magento\Review\Model\Rating\Option $ratingOption */
        $ratingOption = $this->ratingOptionCollection
            ->setPageSize(1)
            ->setCurPage(3)
            ->addRatingFilter($rating->getId())
            ->getFirstItem();

        $query = <<<QUERY
mutation {
  createProductReview(
    input: {
      entity_id: {$entityId}, 
      entity_type: "{$entityType}",
      nickname: "{$nickname}", 
      title: "{$title}", 
      detail: "{$detail}",
      ratings: {
        rating_id: {$rating->getId()},
        option_id: {$ratingOption->getId()}
      }
    }
  ) {
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

QUERY;

        $response = $this->graphQlQuery($query, [], '');

        $this->assertNotEmpty($response['createProductReview']);
        $this->assertInternalType('array', $response['createProductReview']);
        $this->assertNotEmpty($response['createProductReview']['review_id']);
        $this->assertEquals($response['createProductReview']['entity_id'], $entityId);
    }
}

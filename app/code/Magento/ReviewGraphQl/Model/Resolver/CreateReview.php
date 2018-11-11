<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\ResourceModel\Rating as RatingResource;
use Magento\Review\Model\ResourceModel\Rating\Option as RatingOptionResource;
use Magento\Review\Model\Review;
use Magento\Review\Model\ReviewFactory;
use Magento\ReviewGraphQl\Model\Resolver\Product\Reviews\DataProvider as ReviewsDataProvider;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Create Review
 */
class CreateReview implements ResolverInterface
{
    /**
     * Review model
     *
     * @var ReviewFactory
     */
    private $reviewFactory;

    /**
     * Rating model
     *
     * @var RatingFactory
     */
    private $ratingFactory;

    /**
     * @var ReviewsDataProvider
     */
    private $reviewsDataProvider;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var RatingResource
     */
    private $ratingResource;

    /**
     * @var RatingOptionResource
     */
    private $ratingOptionResource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * CreateReview constructor
     *
     * @param ReviewFactory $reviewFactory
     * @param RatingFactory $ratingFactory
     * @param ReviewsDataProvider $reviewsDataProvider
     * @param ProductResource $productResource
     * @param RatingResource $ratingResource
     * @param RatingOptionResource $ratingOptionResource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ReviewFactory $reviewFactory,
        RatingFactory $ratingFactory,
        ReviewsDataProvider $reviewsDataProvider,
        ProductResource $productResource,
        RatingResource $ratingResource,
        RatingOptionResource $ratingOptionResource,
        StoreManagerInterface $storeManager
    ) {
        $this->reviewFactory = $reviewFactory;
        $this->ratingFactory = $ratingFactory;
        $this->reviewsDataProvider = $reviewsDataProvider;
        $this->productResource = $productResource;
        $this->ratingResource = $ratingResource;
        $this->ratingOptionResource = $ratingOptionResource;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $data = $args['input'];
        $customerId = (int)$context->getUserId() ?: null;
        $productId = $this->getProductIdBySku($data['sku']);
        $ratings = !empty($data['ratings']) ? $data['ratings'] : [];

        $review = $this->reviewFactory->create();
        $review
            ->setEntityId($review->getEntityIdByCode(Review::ENTITY_PRODUCT_CODE))
            ->setSku($data['sku'])
            ->setEntityPkValue($productId)
            ->setNickname($data['nickname'])
            ->setTitle($data['title'])
            ->setDetail($data['review_text'])
            ->setStatusId(Review::STATUS_PENDING)
            ->setCustomerId($customerId)
            ->setStoreId($this->storeManager->getStore()->getId())
            ->setStores([$this->storeManager->getStore()->getId()])
            ->save();

        foreach ($ratings as $rating) {
            $ratingId = $this->getRatingIdByName($rating['rating_name']);
            $optionId = $this->getRatingOptionIdByValue($ratingId, $rating['rating_value']);

            $this->ratingFactory->create()
                ->setRatingId($ratingId)
                ->setReviewId($review->getId())
                ->setCustomerId($customerId)
                ->addOptionVote($optionId, $productId);
        }

        $review->aggregate();

        return $this->reviewsDataProvider->getReviewData($review);
    }

    /**
     * Get product id by sku
     *
     * @param string $sku
     * @return false|int
     * @throws GraphQlInputException
     */
    private function getProductIdBySku(string $sku)
    {
        $productId = $this->productResource->getIdBySku($sku);
        if (empty($productId)) {
            throw new GraphQlInputException(__('"sku" has invalid value'));
        }
        return $productId;
    }

    /**
     * Get rating id by name
     *
     * @param string $name
     * @return int
     * @throws GraphQlInputException
     */
    private function getRatingIdByName(string $name)
    {
        $ratingId = $this->ratingResource->getRatingIdByCode($name);
        if (empty($ratingId)) {
            throw new GraphQlInputException(__('"ratings[rating_name]" has invalid value'));
        }
        return $ratingId;
    }

    /**
     * Get rating option id by value
     *
     * @param int $ratingId
     * @param int $value
     * @return int
     * @throws GraphQlInputException
     */
    private function getRatingOptionIdByValue(int $ratingId, int $value)
    {
        $optionId = $this->ratingOptionResource->getOptionIdByRatingIdAndValue($ratingId, $value);
        if (empty($ratingId)) {
            throw new GraphQlInputException(__('"ratings[rating_value]" has invalid value'));
        }
        return $optionId;
    }
}

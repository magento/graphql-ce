<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Review;
use Magento\Review\Model\ReviewFactory;
use Magento\ReviewGraphQl\Model\Reviews\DataProvider as ReviewsDataProvider;
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * CreateReview constructor
     *
     * @param ReviewFactory $reviewFactory
     * @param RatingFactory $ratingFactory
     * @param ReviewsDataProvider $reviewsDataProvider
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ReviewFactory $reviewFactory,
        RatingFactory $ratingFactory,
        ReviewsDataProvider $reviewsDataProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->reviewFactory = $reviewFactory;
        $this->ratingFactory = $ratingFactory;
        $this->reviewsDataProvider = $reviewsDataProvider;
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
        $entityId = (int)$data['entity_id'];
        $entityType = $data['entity_type'];
        $ratings = !empty($data['ratings']) ? $data['ratings'] : [];

        $review = $this->reviewFactory->create();
        $review
            ->setEntityId($review->getEntityIdByCode($entityType))
            ->setEntityPkValue($entityId)
            ->setNickname($data['nickname'])
            ->setTitle($data['title'])
            ->setDetail($data['detail'])
            ->setStatusId(Review::STATUS_PENDING)
            ->setCustomerId($customerId)
            ->setStoreId($this->storeManager->getStore()->getId())
            ->setStores([$this->storeManager->getStore()->getId()])
            ->save();

        foreach ($ratings as $rating) {
            $this->ratingFactory->create()
                ->setRatingId($rating['rating_id'])
                ->setReviewId($review->getId())
                ->setCustomerId($customerId)
                ->addOptionVote($rating['option_id'], $entityId);
        }

        $review->aggregate();

        return $this->reviewsDataProvider->getReviewData($review);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Reviews;

use Magento\Review\Model\ResourceModel\Rating\Option\Vote\CollectionFactory as VoteCollectionFactory;
use Magento\Review\Model\ResourceModel\Review\Collection;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Reviews data provider
 */
class DataProvider
{
    /**
     * Reviews collection
     *
     * @var Collection
     */
    private $reviewCollection;

    /**
     * Review collection factory
     *
     * @var CollectionFactory
     */
    private $reviewCollectionFactory;

    /**
     * Rating vote option model
     *
     * @var VoteCollectionFactory
     */
    protected $voteCollectionFactory;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Review constructor
     *
     * @param CollectionFactory $collectionFactory
     * @param VoteCollectionFactory $voteCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        VoteCollectionFactory $voteCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->reviewCollectionFactory = $collectionFactory;
        $this->voteCollectionFactory = $voteCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Get reviews for specific entity
     *
     * @param string $entityType
     * @param int $entityId
     * @param $pageSize
     * @param $currentPage
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getEntityReviews($entityType, int $entityId, $pageSize, $currentPage): array
    {
        $collection = $this->getReviewsCollection();
        $collection
            ->addEntityFilter($entityType, $entityId)
            ->setPageSize($pageSize)
            ->setCurPage($currentPage)
            ->load();

        $collection->addRateVotes();

        $reviewData = [];
        foreach ($collection as $review) {
            $reviewData[] = $this->getReviewData($review);
        }

        return $reviewData;
    }

    /**
     * Get product review collection
     *
     * @return Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getReviewsCollection()
    {
        if (!$this->reviewCollection) {
            $this->reviewCollection = $this->reviewCollectionFactory->create();
            $this->reviewCollection
                ->addStoreFilter($this->storeManager->getStore()->getId())
                ->addStatusFilter(Review::STATUS_APPROVED)
                ->setDateOrder();
        }
        return $this->reviewCollection;
    }

    /**
     * Get review data
     *
     * @param Review $review
     * @return array
     */
    public function getReviewData(Review $review)
    {
        return [
            'review_id' => $review->getReviewId(),
            'entity_id' => $review->getEntityPkValue(),
            'customer_id' => $review->getCustomerId(),
            'title' => $review->getTitle(),
            'detail' => $review->getDetail(),
            'nickname' => $review->getNickname(),
            'created_at' => $review->getCreatedAt(),
            'rating_votes' => $this->getReviewRatingsData($review),
        ];
    }

    /**
     * Get review ratings data
     *
     * @param Review $review
     * @return array
     */
    private function getReviewRatingsData(Review $review)
    {
        $ratingData = [];

        $ratingVotes = is_array($review->getRatingVotes()) ? $review->getRatingVotes() : $this->getReviewRatingVotes($review);
        foreach ($ratingVotes as $ratingVote) {
            $ratingData[] = [
                'rating_id' => $ratingVote->getRatingId(),
                'rating_code' => $ratingVote->getRatingCode(),
                'percent' => $ratingVote->getPercent(),
                'value' => $ratingVote->getValue(),
            ];
        }

        return $ratingData;
    }

    /**
     * Get review rating votes
     *
     * @param Review $review
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getReviewRatingVotes(Review $review)
    {
        $voteCollection = $this->voteCollectionFactory->create()
            ->setReviewFilter(
                $review->getId()
            )->setStoreFilter(
                $this->storeManager->getStore()->getId()
            )->addRatingInfo(
                $this->storeManager->getStore()->getId()
            )->load();

        $review->setRatingVotes($voteCollection);

        return $voteCollection;
    }
}

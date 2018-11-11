<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Product\Reviews;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Review\Api\Data\ReviewSearchResultsInterfaceFactory;
use Magento\Review\Model\RatingFactory;
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
     * Review search result factory
     *
     * @var ReviewSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * Rating vote option model
     *
     * @var VoteCollectionFactory
     */
    private $voteCollectionFactory;

    /**
     * Rating model
     *
     * @var RatingFactory
     */
    private $ratingFactory;

    /**
     * Catalog product resource
     *
     * @var ProductResource
     */
    private $productResource;

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
     * @param ReviewSearchResultsInterfaceFactory $searchResultsFactory
     * @param VoteCollectionFactory $voteCollectionFactory
     * @param RatingFactory $ratingFactory
     * @param ProductResource $productResource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        ReviewSearchResultsInterfaceFactory $searchResultsFactory,
        VoteCollectionFactory $voteCollectionFactory,
        RatingFactory $ratingFactory,
        ProductResource $productResource,
        StoreManagerInterface $storeManager
    ) {
        $this->reviewCollectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->voteCollectionFactory = $voteCollectionFactory;
        $this->ratingFactory = $ratingFactory;
        $this->productResource = $productResource;
        $this->storeManager = $storeManager;
    }

    /**
     * Gets list of reviews for given search criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    ): SearchResultsInterface
    {
        $collection = $this->getReviewsCollection();

        foreach ($searchCriteria->getFilterGroups() as $group) {
            $this->addFilterGroupToCollection($group, $collection);
        }

        $collection->setPageSize($searchCriteria->getPageSize());
        $collection->setCurPage($searchCriteria->getCurrentPage());

        if (!empty($searchCriteria->getSortOrders())) {
            foreach ($searchCriteria->getSortOrders() as $sortOrder) {
                $collection->setOrder($sortOrder->getField(), $sortOrder->getDirection());
            }
        } else {
            $collection->setDateOrder();
        }

        $collection->addRateVotes();
        $this->addAverageRating($collection);

        $items = [];
        foreach ($collection as $review) {
            $items[] = $this->getReviewData($review);
        }

        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($items);
        $searchResult->setTotalCount($collection->getSize());
        return $searchResult;
    }

    /**
     * Helper function that adds a FilterGroup to the collection.
     *
     * @param FilterGroup $filterGroup
     * @param Collection $collection
     * @return void
     */
    private function addFilterGroupToCollection(
        FilterGroup $filterGroup,
        Collection $collection
    ) {
        $fields = [];
        $conditions = [];
        foreach ($filterGroup->getFilters() as $filter) {
            switch ($filter->getField()) {
                case 'customer_id':
                    $collection->addCustomerFilter($filter->getValue());
                    break;
                case 'sku':
                    $productId = $this->productResource->getIdBySku($filter->getValue());
                    $collection->addFieldToFilter('entity_pk_value', $productId);
                    break;
                default:
                    $condition = $filter->getConditionType() ?: 'eq';
                    $fields[] = $filter->getField();
                    $conditions[] = [$condition => $filter->getValue()];
            }
        }

        if ($fields) {
            $collection->addFieldToFilter($fields, $conditions);
        }
    }

    /**
     * Get product review collection
     *
     * @return Collection
     */
    private function getReviewsCollection()
    {
        if (!$this->reviewCollection) {
            $collection = $this->reviewCollectionFactory->create();
            $collection->addStoreFilter($this->storeManager->getStore()->getId());
            $collection->addProductData(['sku']);

            $this->reviewCollection = $collection;
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
            'sku' => $review->getSku(),
            'title' => $review->getTitle(),
            'review_text' => $review->getDetail(),
            'nickname' => $review->getNickname(),
            'created_at' => $review->getCreatedAt(),
            'average_rating' => $review->getAverageRating() ?: $this->getReviewAverageRating($review->getId()),
            'ratings' => $this->getReviewRatingsData($review),
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
                'name' => $ratingVote->getRatingCode(),
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
     * @return \Magento\Review\Model\ResourceModel\Rating\Option\Vote\Collection
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

    /**
     * Get review summary
     *
     * @param Collection $collection
     * @return Collection
     */
    private function addAverageRating(Collection $collection)
    {
        foreach ($collection as $review) {
            $averageRating = $this->getReviewAverageRating($review->getId());
            $review->setAverageRating($averageRating);
        }

        return $collection;
    }

    /**
     * Get review average rating
     *
     * @param int $reviewId
     * @return float
     */
    private function getReviewAverageRating($reviewId)
    {
        $reviewSummary = $this->ratingFactory->create()->getReviewSummary($reviewId);
        return (float)($reviewSummary->getData('sum') / $reviewSummary->getData('count'));
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Product\DataProvider;

use Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Product review data provider
 */
class Review
{
    /**
     * Product reviews collection
     *
     * @var \Magento\Review\Model\ResourceModel\Review\Product\Collection
     */
    private $reviewCollection;

    /**
     * Review resource model
     *
     * @var CollectionFactory
     */
    private $reviewCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Review constructor
     *
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->reviewCollectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param int $customerId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getData(int $customerId): array
    {
        $reviewData = [];

        /** @var \Magento\Review\Model\Review $review */
        $reviews = $this->getReviewCollection($customerId);
        foreach ($reviews as $review) {
            $reviewData[] = [
                'review_id' => $review->getReviewId(),
                'entity_id' => $review->getEntityId(),
                'store_id' => $review->getStoreId(),
                'entity_name' => $review->getName(),
                'title' => $review->getTitle(),
                'detail' => $review->getDetail(),
                'sum' => $review->getSum(),
                'count' => $review->getCount(),
                'nickname' => $review->getNickname(),
                'created_at' => $review->getCreatedAt(),
            ];
        }

        return $reviewData;
    }

    /**
     * Get review collection
     *
     * @param int $customerId
     * @return \Magento\Review\Model\ResourceModel\Review\Product\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getReviewCollection(int $customerId)
    {
        if (!$this->reviewCollection) {
            $this->reviewCollection = $this->reviewCollectionFactory->create()
                ->addStoreFilter($this->storeManager->getStore()->getId())
                ->addCustomerFilter($customerId)
                ->setDateOrder()
                ->load()
                ->addReviewSummary();
        }
        return $this->reviewCollection;
    }
}

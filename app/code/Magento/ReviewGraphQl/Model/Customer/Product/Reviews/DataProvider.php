<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Customer\Product\Reviews;

use Magento\Review\Model\ResourceModel\Review\Product\Collection;
use Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Customer product reviews data provider
 */
class DataProvider
{
    /**
     * Product reviews collection
     *
     * @var Collection
     */
    private $reviewCollection;

    /**
     * Product review collection factory
     *
     * @var CollectionFactory
     */
    private $reviewCollectionFactory;

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
     * Get product reviews by customer id
     *
     * @param int|null $customerId
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByCustomerId(int $customerId): array
    {
        $collection = $this->getReviewsCollection();
        $collection->addCustomerFilter($customerId);
        $collection->load();
        $collection->addReviewSummary();

        return $this->getReviewsData($collection);
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
                ->setDateOrder();
        }
        return $this->reviewCollection;
    }

    /**
     * Get reviews data
     *
     * @param Collection $collection
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getReviewsData(Collection $collection)
    {
        $reviewData = [];

        /** @var \Magento\Review\Model\Review $review */
        foreach ($collection as $review) {
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
                'status_id' => $review->getStatusId(),
                'created_at' => $review->getCreatedAt(),
            ];
        }
        return $reviewData;
    }
}

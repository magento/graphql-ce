<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Product\Ratings;

use Magento\Review\Model\ResourceModel\Rating\CollectionFactory;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Reviews data provider
 */
class DataProvider
{
    /**
     * Review collection factory
     *
     * @var CollectionFactory
     */
    private $ratingCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Review constructor
     *
     * @param CollectionFactory $ratingCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $ratingCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->ratingCollectionFactory = $ratingCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Gets ratings
     *
     * @return array
     */
    public function getRatings()
    {
        /** @var \Magento\Review\Model\ResourceModel\Rating\Collection $collection */
        $collection = $this->ratingCollectionFactory->create();
        $collection->addEntityFilter(Review::ENTITY_PRODUCT_CODE);
        $collection->setStoreFilter($this->storeManager->getStore()->getId());
        $collection->setActiveFilter();

        $ratings = [];
        foreach ($collection as $rating) {
            $ratings['items'][] = [
                'name' => $rating->getRatingCode(),
                'position' => $rating->getPosition(),
            ];
        }

        return $ratings;
    }
}

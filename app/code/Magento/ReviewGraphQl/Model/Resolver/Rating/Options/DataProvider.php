<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Rating\Options;

use Magento\Review\Model\ResourceModel\Rating\Option\CollectionFactory;

/**
 * Reviews data provider
 */
class DataProvider
{
    /**
     * Rating options collection factory
     *
     * @var CollectionFactory
     */
    private $ratingOptionsCollectionFactory;

    /**
     * Review constructor
     *
     * @param CollectionFactory $ratingOptionsCollectionFactory
     */
    public function __construct(
        CollectionFactory $ratingOptionsCollectionFactory
    ) {
        $this->ratingOptionsCollectionFactory = $ratingOptionsCollectionFactory;
    }

    /**
     * Gets ratings
     *
     * @param int $ratingId
     * @return array
     */
    public function getOptions(int $ratingId): array
    {
        /** @var \Magento\Review\Model\ResourceModel\Rating\Option\Collection $collection */
        $collection = $this->ratingOptionsCollectionFactory->create();
        $collection->addRatingFilter($ratingId);
        $collection->setPositionOrder();

        /** @var \Magento\Review\Model\Rating\Option $option */

        $options = [];
        foreach ($collection as $option) {
            $options['items'][] = [
                'model' => $option,
                'code' => $option->getCode(),
                'value' => $option->getValue(),
                'position' => $option->getPosition(),
            ];
        }

        return $options;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Visibility;

/**
 * {@inheritdoc}
 */
class CollectionBuilder implements CollectionBuilderInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * CollectionBuilder constructor.
     * @param CollectionFactory $collectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param Visibility $visibility
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        Visibility $visibility
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->visibility = $visibility;
    }

    /**
     * @inheritdoc
     */
    public function build(
        SearchCriteriaInterface $searchCriteria,
        array $attributeNames,
        bool $isSearch = false,
        bool $isChildSearch = false
    ): Collection {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($collection, $searchCriteria, $attributeNames);

        if (!$isChildSearch) {
            $visibilityIds = $isSearch
                ? $this->visibility->getVisibleInSearchIds()
                : $this->visibility->getVisibleInCatalogIds();
            $collection->setVisibility($visibilityIds);
        }

        return $collection;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsGraphQl\Model\Resolver\DataProvider;

use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Model\Block as BlockModel;
use Magento\Cms\Model\ResourceModel\Block\Collection as BlockCollection;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as BlockCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Widget\Model\Template\FilterEmulate;

/**
 * Cms block data provider
 */
class Block
{
    /**
     * @var FilterEmulate
     */
    private $widgetFilter;

    /**
     * @var BlockCollectionFactory
     */
    private $blockCollectionFactory;

    /**
     * @param BlockCollectionFactory $blockCollectionFactory
     * @param FilterEmulate $widgetFilter
     */
    public function __construct(
        BlockCollectionFactory $blockCollectionFactory,
        FilterEmulate $widgetFilter
    ) {
        $this->widgetFilter = $widgetFilter;
        $this->blockCollectionFactory = $blockCollectionFactory;
    }

    /**
     * Get block data
     *
     * @param string $blockIdentifier
     * @param Store $currentStore
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(string $blockIdentifier, Store $currentStore): array
    {
        $filterBy = BlockInterface::IDENTIFIER;
        $storeId = (int)$currentStore->getId();
        if (is_numeric($blockIdentifier)) {
            $filterBy = BlockInterface::BLOCK_ID;
        }

        /** @var BlockCollection $collection */
        $collection = $this->blockCollectionFactory->create();
        $collection->addFieldToFilter($filterBy, ["eq" => $blockIdentifier]);
        $collection->addFieldToFilter("store_id", ["eq" => $storeId]);

        /** @var BlockModel $block */
        $block = $collection->getFirstItem();
        if (false === $block->isActive()) {
            throw new NoSuchEntityException(
                __('The CMS block with the "%1" ID doesn\'t exist.', $blockIdentifier)
            );
        }

        $renderedContent = $this->widgetFilter->filter($block->getContent());

        $blockData = [
            BlockInterface::BLOCK_ID => $block->getId(),
            BlockInterface::IDENTIFIER => $block->getIdentifier(),
            BlockInterface::TITLE => $block->getTitle(),
            BlockInterface::CONTENT => $renderedContent,
        ];
        return $blockData;
    }
}

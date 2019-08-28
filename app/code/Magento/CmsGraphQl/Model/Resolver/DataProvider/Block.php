<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsGraphQl\Model\Resolver\DataProvider;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Model\GetBlockByIdentifier;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Model\Template\FilterEmulate;

/**
 * Cms block data provider
 */
class Block
{
    /**
     * @var BlockRepositoryInterface
     */
    private $blockRepository;

    /**
     * @var FilterEmulate
     */
    private $widgetFilter;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param BlockRepositoryInterface $blockRepository
     * @param FilterEmulate $widgetFilter
     * @param StoreManagerInterface $storeManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        BlockRepositoryInterface $blockRepository,
        FilterEmulate $widgetFilter,
        StoreManagerInterface $storeManager,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->blockRepository = $blockRepository;
        $this->widgetFilter = $widgetFilter;
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get block data
     *
     * @param string $blockIdentifier
     * @return array
     * @throws LocalizedException
     */
    public function getData(string $blockIdentifier): array
    {
        $filterBy = BlockInterface::IDENTIFIER;
        $storeId = (int)$this->storeManager->getStore()->getId();
        if (is_numeric($blockIdentifier)) {
            $filterBy = BlockInterface::BLOCK_ID;
        }
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'store_id',
            $storeId,
            'eq'
        )->addFilter(
            $filterBy,
            $blockIdentifier,
            'eq'
        )->setPageSize(1)->setCurrentPage(1)->create();

        $blocks = $this->blockRepository->getList($searchCriteria)->getItems();

        if (count($blocks) != 1) {
            throw new NoSuchEntityException(
                __('The CMS block with the "%1" ID doesn\'t exist.', $blockIdentifier)
            );
        }

        $block = array_values($blocks)[0];
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

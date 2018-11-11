<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Product\Reviews;

use Magento\Framework\ObjectManagerInterface;

/**
 * Class SearchResultFactory
 * @package Magento_ReviewGraphQl
 */
class SearchResultFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Instantiate SearchResult
     *
     * @param int $totalCount
     * @param array $reviewsSearchResult
     * @return SearchResult
     */
    public function create(int $totalCount, array $reviewsSearchResult) : SearchResult
    {
        return $this->objectManager->create(
            SearchResult::class,
            ['totalCount' => $totalCount, 'reviewsSearchResult' => $reviewsSearchResult]
        );
    }
}

<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Review\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * @api
 * @since 100.0.2
 */
interface ReviewSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get review list.
     *
     * @return \Magento\Review\Api\Data\ReviewInterface[]
     */
    public function getItems();

    /**
     * Set review list.
     *
     * @param \Magento\Review\Api\Data\ReviewInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

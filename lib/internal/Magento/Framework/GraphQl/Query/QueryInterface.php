<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\GraphQl\Query;

/**
 * Interface QueryInterface
 *
 * Contains information about GraphQl query
 */
interface QueryInterface
{
    /**
     * TBP
     */
    public function getStructure();

    /**
     * TBP
     */
    public function getArgs();

    /**
     * TBP
     */
    public function getVariables();
}

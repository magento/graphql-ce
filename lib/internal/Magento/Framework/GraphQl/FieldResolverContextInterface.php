<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\GraphQl;

/**
 * Interface FieldResolverContextInterface
 *
 * Field resolver context
 */
interface FieldResolverContextInterface
{
    /**
     * TBP
     */
    public function getStore();

    /**
     * TBP
     */
    public function getUser();
}

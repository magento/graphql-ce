<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\GraphQl;

use Magento\Framework\GraphQl\Query\QueryInterface;

/**
 * Interface FieldResolverInterface
 *
 * Field resolver
 */
interface FieldResolverInterface
{
    /**
     * @param FieldResolverContextInterface $resolverContext
     * @param QueryInterface $query
     * @param FieldConfigInterface $fieldConfig
     * @param array $parentResolvedValue
     * @return array
     */
    public function resolve(
        FieldResolverContextInterface $resolverContext,
        QueryInterface $query,
        FieldConfigInterface $fieldConfig,
        array $parentResolvedValue
    );
}
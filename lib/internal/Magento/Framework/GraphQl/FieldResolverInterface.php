<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\GraphQl;

use Magento\Framework\GraphQl\Query\QueryInterface;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

/**
 * Interface FieldResolverInterface
 *
 * Field resolver
 */
interface FieldResolverInterface
{
    /**
     * @param ContextInterface $resolverContext
     * @param QueryInterface $query
     * @param FieldConfigInterface $fieldConfig
     * @param array|null $parentResolvedValue
     * @return array
     */
    public function resolve(
        ContextInterface $resolverContext,
        QueryInterface $query,
        FieldConfigInterface $fieldConfig,
        ?array $parentResolvedValue
    );
}

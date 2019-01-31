<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleGraphQl\Model;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;
use Magento\Bundle\Model\Product\Type;

/**
 * {@inheritdoc}
 */
class BundleProductTypeResolver implements TypeResolverInterface
{
    /**
     * Configurable product type resolver code
     */
    const TYPE_RESOLVER = 'BundleProduct';

    /**
     * {@inheritdoc}
     */
    public function resolveType(array $data) : string
    {
        if (isset($data['type_id']) && $data['type_id'] == Type::TYPE_CODE) {
            return self::TYPE_RESOLVER;
        }
        return '';
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\ReviewGraphQl\Model\Resolver\Product\Reviews as ProductReviewsResolver;

/**
 * Class ProductReviewsProvider
 * @package Magento_ReviewGraphQl
 */
class ProductReviews extends ProductReviewsResolver
{
    /**
     * {@inheritdoc}
     */
    protected function getSku($args, $value): string
    {
        if (!is_array($value) || !array_key_exists('model', $value) || !$value['model'] instanceof ProductInterface) {
            throw new GraphQlInputException(__('"model" value should be specified'));
        }

        /* @var $product ProductInterface */
        $product = $value['model'];

        return $product->getSku();
    }
}

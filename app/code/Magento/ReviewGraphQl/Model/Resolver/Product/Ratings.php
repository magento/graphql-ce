<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Product;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\ReviewGraphQl\Model\Resolver\Product\Ratings\DataProvider as RatingsDataProvider;

/**
 * Class Ratings
 * @package Magento_ReviewGraphQl
 */
class Ratings implements ResolverInterface
{
    /**
     * @var RatingsDataProvider
     */
    private $ratingsDataProvider;

    /**
     * Reviews constructor
     * @param RatingsDataProvider $ratingsDataProvider
     */
    public function __construct(
        RatingsDataProvider $ratingsDataProvider
    ) {
        $this->ratingsDataProvider = $ratingsDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        return $this->ratingsDataProvider->getRatings();
    }
}

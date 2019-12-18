<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Rating;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Review\Api\Data\RatingInterface;
use Magento\ReviewGraphQl\Model\Resolver\Rating\Options\DataProvider as RatingOptionsDataProvider;

/**
 * Class Options
 * @package Magento_ReviewGraphQl
 */
class Options implements ResolverInterface
{
    /**
     * @var RatingOptionsDataProvider
     */
    private $ratingOptionsDataProvider;

    /**
     * Reviews constructor
     * @param RatingOptionsDataProvider $ratingOptionsDataProvider
     */
    public function __construct(
        RatingOptionsDataProvider $ratingOptionsDataProvider
    ) {
        $this->ratingOptionsDataProvider = $ratingOptionsDataProvider;
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
        if (!is_array($value) || !array_key_exists('model', $value) || !$value['model'] instanceof RatingInterface) {
            throw new GraphQlInputException(__('"model" value should be specified'));
        }

        /* @var RatingInterface */
        $rating = $value['model'];

        return $this->ratingOptionsDataProvider->getOptions((int)$rating->getId());
    }
}

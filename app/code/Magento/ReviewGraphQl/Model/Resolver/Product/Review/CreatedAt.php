<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Product\Review;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class Reviews
 * @package Magento_ReviewGraphQl
 */
class CreatedAt implements ResolverInterface
{
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * Reviews constructor
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        TimezoneInterface $timezone
    ) {
        $this->timezone = $timezone;
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
        if (!isset($value['model'])) {
            throw new GraphQlInputException(__('"model" value should be specified'));
        }

        /* @var \Magento\Review\Model\Review $review */
        $review = $value['model'];

        return $this->timezone->formatDate(
            $review->getCreatedAt(),
            \IntlDateFormatter::MEDIUM,
            false
        );
    }
}

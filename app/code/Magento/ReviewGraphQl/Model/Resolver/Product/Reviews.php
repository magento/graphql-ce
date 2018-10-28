<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Product;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Review\Model\Review;
use Magento\ReviewGraphQl\Model\Reviews\DataProvider as ReviewsDataProvider;

/**
 * Product reviews resolver
 */
class Reviews implements ResolverInterface
{
    /**
     * @var ReviewsDataProvider
     */
    private $reviewsDataProvider;

    /**
     * Reviews constructor
     *
     * @param ReviewsDataProvider $reviewDataProvider
     */
    public function __construct(
        ReviewsDataProvider $reviewDataProvider
    ) {
        $this->reviewsDataProvider = $reviewDataProvider;
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
        $entityId = $this->getEntityId($args);

        return [
            'items' => $this->getReviewsData($entityId, $args['pageSize'], $args['currentPage'])
        ];
    }

    /**
     * @param array $args
     * @return int
     * @throws GraphQlInputException
     */
    private function getEntityId(array $args): int
    {
        if (!isset($args['entity_id'])) {
            throw new GraphQlInputException(__('"entity_id" should be specified'));
        }

        return (int)$args['entity_id'];
    }

    /**
     * Get reviews data
     *
     * @param int $productId
     * @param int $pageSize
     * @param int $currentPage
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    private function getReviewsData(int $productId, int $pageSize, int $currentPage): array
    {
        try {
            $reviewData = $this->reviewsDataProvider->getEntityReviews(
                Review::ENTITY_PRODUCT_CODE,
                $productId,
                $pageSize,
                $currentPage
            );
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $reviewData;
    }
}

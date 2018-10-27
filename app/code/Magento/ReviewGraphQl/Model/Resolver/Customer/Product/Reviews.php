<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Customer\Product;

use Magento\CustomerGraphQl\Model\Customer\CheckCustomerAccountInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\ReviewGraphQl\Model\Customer\Product\Reviews\DataProvider as ReviewsDataProvider;

/**
 * Customer Product Reviews
 */
class Reviews implements ResolverInterface
{
    /**
     * @var CheckCustomerAccountInterface
     */
    private $checkCustomerAccount;

    /**
     * @var ReviewsDataProvider
     */
    private $reviewsDataProvider;

    /**
     * Reviews constructor
     *
     * @param CheckCustomerAccountInterface $checkCustomerAccount
     * @param ReviewsDataProvider $reviewDataProvider
     */
    public function __construct(
        CheckCustomerAccountInterface $checkCustomerAccount,
        ReviewsDataProvider $reviewDataProvider
    ) {
        $this->checkCustomerAccount = $checkCustomerAccount;
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
        $customerId = (int)$context->getUserId();

        $this->checkCustomerAccount->execute($customerId, $context->getUserType());

        return [
            'items' => $this->getReviewsData($customerId)
        ];
    }

    /**
     * Get reviews data
     *
     * @param int $customerId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getReviewsData(int $customerId): array
    {
        try {
            $reviewData = $this->reviewsDataProvider->getByCustomerId($customerId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $reviewData;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Product;

use Magento\Authorization\Model\UserContextInterface;
use Magento\CustomerGraphQl\Model\Customer\CheckCustomerAccountInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\ReviewGraphQl\Model\Resolver\Product\DataProvider\Review as ReviewDataProvider;

/**
 * Customer Product Reviews
 */
class Reviews implements ResolverInterface
{
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var CheckCustomerAccountInterface
     */
    private $checkCustomerAccount;

    /**
     * @var ReviewDataProvider
     */
    private $reviewDataProvider;

    /**
     * Reviews constructor
     *
     * @param UserContextInterface $userContext
     * @param CheckCustomerAccountInterface $checkCustomerAccount
     * @param ReviewDataProvider $reviewDataProvider
     */
    public function __construct(
        UserContextInterface $userContext,
        CheckCustomerAccountInterface $checkCustomerAccount,
        ReviewDataProvider $reviewDataProvider
    ) {
        $this->userContext = $userContext;
        $this->checkCustomerAccount = $checkCustomerAccount;
        $this->reviewDataProvider = $reviewDataProvider;
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
        $customerId = (int)$this->userContext->getUserId();
        $customerType = $this->userContext->getUserType();

        $this->checkCustomerAccount->execute($customerId, $customerType);

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
     */
    private function getReviewsData(int $customerId): array
    {
        try {
            $reviewData = $this->reviewDataProvider->getData($customerId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $reviewData;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerGraphQl\Model\Customer;

/**
 * Interface CheckCustomerAccountInterface
 */
interface CheckCustomerAccountInterface
{
    /**
     * Check customer account
     *
     * @param int|null $customerId
     * @param int|null $customerType
     * @return void
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException
     * @throws \Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(?int $customerId, ?int $customerType): void;
}

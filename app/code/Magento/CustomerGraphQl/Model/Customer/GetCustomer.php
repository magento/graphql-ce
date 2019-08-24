<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGraphQl\Model\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AuthenticationInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Get customer
 */
class GetCustomer
{
    /**
     * @var AuthenticationInterface
     */
    private $authentication;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @var ScopeValidate
     */
    private $scopeValidate;

    /**
     * @param AuthenticationInterface $authentication
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $accountManagement
     * @param ScopeValidate $scopeValidate
     */
    public function __construct(
        AuthenticationInterface $authentication,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        ScopeValidate $scopeValidate
    ) {
        $this->authentication = $authentication;
        $this->customerRepository = $customerRepository;
        $this->accountManagement = $accountManagement;
        $this->scopeValidate = $scopeValidate;
    }

    /**
     * Get customer
     *
     * @param ContextInterface $context
     * @return CustomerInterface
     * @throws GraphQlAuthenticationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function execute(ContextInterface $context): CustomerInterface
    {
        $currentUserId = $context->getUserId();

        try {
            $customer = $this->customerRepository->getById($currentUserId);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(
                __('Customer with id "%customer_id" does not exist.', ['customer_id' => $currentUserId]),
                $e
            );
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        try {
            $this->scopeValidate->execute($customer, $context);
        } catch (LocalizedException $e) {
            throw new GraphQlAuthenticationException(__('The current customer isn\'t authorized.'), $e);
        }

        if (true === $this->authentication->isLocked($currentUserId)) {
            throw new GraphQlAuthenticationException(__('The account is locked.'));
        }

        try {
            $confirmationStatus = $this->accountManagement->getConfirmationStatus($currentUserId);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        if ($confirmationStatus === AccountManagementInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
            throw new GraphQlAuthenticationException(__("This account isn't confirmed. Verify and try again."));
        }
        return $customer;
    }
}

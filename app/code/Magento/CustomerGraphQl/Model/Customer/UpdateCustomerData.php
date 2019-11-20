<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGraphQl\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAlreadyExistsException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Update customer account information
 */
class UpdateCustomerData
{
    /**
     * @var SaveCustomer
     */
    private $saveCustomer;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var ChangeSubscriptionStatus
     */
    private $changeSubscriptionStatus;

    /**
     * @var ValidateCustomerData
     */
    private $validateCustomerData;

    /**
     * @param SaveCustomer $saveCustomer
     * @param DataObjectHelper $dataObjectHelper
     * @param ChangeSubscriptionStatus $changeSubscriptionStatus
     * @param ValidateCustomerData $validateCustomerData
     */
    public function __construct(
        SaveCustomer $saveCustomer,
        DataObjectHelper $dataObjectHelper,
        ChangeSubscriptionStatus $changeSubscriptionStatus,
        ValidateCustomerData $validateCustomerData
    ) {
        $this->saveCustomer = $saveCustomer;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->changeSubscriptionStatus = $changeSubscriptionStatus;
        $this->validateCustomerData = $validateCustomerData;
    }

    /**
     * Update customer account
     *
     * @param CustomerInterface $customer
     * @param array $data
     * @param StoreInterface $store
     * @return void
     * @throws GraphQlAlreadyExistsException
     * @throws GraphQlInputException
     */
    public function execute(CustomerInterface $customer, array $data, StoreInterface $store): void
    {
        $this->validateCustomerData->execute($data);
        $this->dataObjectHelper->populateWithArray($customer, $data, CustomerInterface::class);
        $customer->setStoreId($store->getId());

        $this->saveCustomer->execute($customer);

        if (isset($data['is_subscribed'])) {
            $this->changeSubscriptionStatus->execute((int)$customer->getId(), (bool)$data['is_subscribed']);
        }
    }
}

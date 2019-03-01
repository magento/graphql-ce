<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGraphQl\Model\Customer\Address;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\CustomerGraphQl\Model\Customer\Address\Validator as CustomerAddressValidator;

/**
 * Customer address create data validator
 */
class CustomerAddressCreateDataValidator
{
    /**
     * @var CustomerAddressValidator
     */
    private $customerAddressValidator;

    /**
     * @param CustomerAddressValidator $addressValidator
     */
    public function __construct(
        CustomerAddressValidator $addressValidator
    ) {
        $this->customerAddressValidator = $addressValidator;
    }

    /**
     * @param array $addressData
     * @throws GraphQlInputException
     * @throws \Exception
     */
    public function validate(array $addressData): void
    {
        $errors = $this->customerAddressValidator->validateAddress($addressData);

        $errorInput = [];

        if ($errors !== true) {
            foreach ($errors as $messageText) {
                $errorInput[] = $messageText;
            }
        }

        if ($errorInput) {
            throw new GraphQlInputException(
                __('Required parameters are missing: %1', [implode(', ', $errorInput)])
            );
        }
    }
}

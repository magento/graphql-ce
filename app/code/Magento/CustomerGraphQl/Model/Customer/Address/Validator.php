<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGraphQl\Model\Customer\Address;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RequestInterfaceFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Customer\Model\Metadata\Form;

class Validator
{
    /**
     * @var RequestInterfaceFactory
     */
    private $requestFactory;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @param RequestInterfaceFactory $requestFactory
     * @param FormFactory $formFactory
     */
    public function __construct(
        RequestInterfaceFactory $requestFactory,
        FormFactory $formFactory
    ) {
        $this->requestFactory = $requestFactory;
        $this->formFactory = $formFactory;
    }

    /**
     * @param array $addressData
     * @return array|bool
     */
    public function validateAddress($addressData)
    {
        $request = $this->createNewRequest();
        $request->setParams($addressData);

        /** @var Form $addressForm */
        $addressForm = $this->formFactory->create(
            'customer_address',
            'customer_address_edit'
        );

        $addressData = $addressForm->extractData($request);

        $errors = $addressForm->validateData($addressData);
        if ($errors !== true) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error;
            }
        }

        return $messages ?? true;
    }

    /**
     * @return RequestInterface
     */
    private function createNewRequest()
    {
        return $this->requestFactory->create();
    }
}

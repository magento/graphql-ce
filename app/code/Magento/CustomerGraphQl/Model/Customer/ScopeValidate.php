<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGraphQl\Model\Customer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Config\Share;
use Magento\Framework\Exception\AuthenticationException;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Validate customer is allowed in current store scope
 */
class ScopeValidate
{
    /**
     * @var Share
     */
    private $shareConfig;

    /**
     * @param Share $shareConfig
     */
    public function __construct(
        Share $shareConfig
    ) {
        $this->shareConfig = $shareConfig;
    }

    /**
     * Validate customer is allowed in current store scope
     *
     * @param CustomerInterface $customer
     * @param ContextInterface $context
     * @throws AuthenticationException
     */
    public function execute(CustomerInterface $customer, ContextInterface $context): void
    {
        if ($this->shareConfig->isGlobalScope()) {
            return;
        }

        $websiteId = $context->getExtensionAttributes()->getStore()->getWebsiteId();
        if ($customer->getWebsiteId() !== $websiteId) {
            throw new AuthenticationException(
                __(
                    'Customer with "%customerId" is not authorized in websiteId "%websiteId"',
                    [
                        'customerId' => $customer->getId(),
                        'websiteId' => $websiteId
                    ]
                )
            );
        }
    }
}

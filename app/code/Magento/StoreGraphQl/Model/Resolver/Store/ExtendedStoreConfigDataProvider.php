<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\StoreGraphQl\Model\Resolver\Store;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Extends default StoreConfigInterface for GraphQL request processing.
 */
class ExtendedStoreConfigDataProvider
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var array
     */
    private $extendedConfigs;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param array $extendedConfigs
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        array $extendedConfigs
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->extendedConfigs = $extendedConfigs;
    }

    /**
     * Get data from ScopeConfig by path's defined in DI config
     * @return array
     */
    public function getExtendedConfigs()
    {
        $extendedConfigsData = [];
        foreach ($this->extendedConfigs as $key => $path) {
            $extendedConfigsData[$key] = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
        }

        return $extendedConfigsData;
    }
}

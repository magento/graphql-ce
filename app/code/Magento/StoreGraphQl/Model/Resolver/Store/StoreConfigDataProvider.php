<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\StoreGraphQl\Model\Resolver\Store;

use Magento\Store\Api\Data\StoreConfigInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * StoreConfig field data provider, used for GraphQL request processing.
 */
class StoreConfigDataProvider
{
    /**
     * @var StoreConfigManagerInterface
     */
    private $storeConfigManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param StoreConfigManagerInterface $storeConfigManager
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreConfigManagerInterface $storeConfigManager,
        StoreManagerInterface $storeManager
    ) {
        $this->storeConfigManager = $storeConfigManager;
        $this->storeManager = $storeManager;
    }

    /**
     * Get store config for current store
     *
     * @return array
     */
    public function getStoreConfig() : array
    {
        $store = $this->storeManager->getStore();
        $storeConfig = current($this->storeConfigManager->getStoreConfigs([$store->getCode()]));

        return $this->hydrateStoreConfig($storeConfig);
    }

    /**
     * Transform StoreConfig object to in array format
     *
     * @param StoreConfigInterface $storeConfig
     * @return array
     */
    private function hydrateStoreConfig($storeConfig): array
    {
        /** @var StoreConfigInterface $storeConfig */
        $storeConfigData = [
            'id' => $storeConfig->getId(),
            'code' => $storeConfig->getCode(),
            'website_id' => $storeConfig->getWebsiteId(),
            'locale' => $storeConfig->getLocale(),
            'base_currency_code' => $storeConfig->getBaseCurrencyCode(),
            'default_display_currency_code' => $storeConfig->getDefaultDisplayCurrencyCode(),
            'timezone' => $storeConfig->getTimezone(),
            'weight_unit' => $storeConfig->getWeightUnit(),
            'base_url' => $storeConfig->getBaseUrl(),
            'base_link_url' => $storeConfig->getBaseLinkUrl(),
            'base_static_url' => $storeConfig->getSecureBaseStaticUrl(),
            'base_media_url' => $storeConfig->getBaseMediaUrl(),
            'secure_base_url' => $storeConfig->getSecureBaseUrl(),
            'secure_base_link_url' => $storeConfig->getSecureBaseLinkUrl(),
            'secure_base_static_url' => $storeConfig->getSecureBaseStaticUrl(),
            'secure_base_media_url' => $storeConfig->getSecureBaseMediaUrl()
        ];
        return $storeConfigData;
    }
}

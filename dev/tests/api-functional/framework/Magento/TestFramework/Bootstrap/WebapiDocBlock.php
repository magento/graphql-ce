<?php
/**
 * Bootstrap of the custom Web API DocBlock annotations.
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TestFramework\Bootstrap;

class WebapiDocBlock extends \Magento\TestFramework\Bootstrap\DocBlock
{
    /**
     * Get list of subscribers. In addition, register magentoApiDataFixture and magentoApiConfigFixture
     * annotation processors
     *
     * @param \Magento\TestFramework\Application $application
     * @return array
     */
    protected function _getSubscribers(\Magento\TestFramework\Application $application)
    {
        $subscribers = parent::_getSubscribers($application);
        $subscribers[] = new \Magento\TestFramework\Annotation\ApiDataFixture($this->_fixturesBaseDir);
        $subscribers[] = new \Magento\TestFramework\Annotation\ApiConfigFixture($this->_fixturesBaseDir);
        return $subscribers;
    }
}

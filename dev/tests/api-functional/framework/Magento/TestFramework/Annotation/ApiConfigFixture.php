<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\TestFramework\Annotation;

/**
 * Processor for magentoApiConfigFixture annotation
 */
class ApiConfigFixture extends \Magento\TestFramework\Annotation\ConfigFixture
{
    /**
     * @var string
     */
    protected $annotation = 'magentoApiConfigFixture';

    /**
     * Reassign configuration data whenever application is reset
     */
    public function initStoreAfter()
    {
    }
}

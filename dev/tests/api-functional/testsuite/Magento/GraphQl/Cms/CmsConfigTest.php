<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Cms;

use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test the GraphQL Cms related StoreConfigs query
 */
class CmsConfigTest extends GraphQlAbstract
{
    /**
     * @magentoApiConfigFixture default_store web/default/front Default Store Front
     * @magentoApiConfigFixture default_store web/default/cms_home_page Default Store Homepage
     * @magentoApiConfigFixture default_store web/default/no_route default_store_no_route
     * @magentoApiConfigFixture default_store web/default/cms_no_route default_store_cms_no_route
     * @magentoApiConfigFixture default_store web/default/cms_no_cookies default_store_cms_no_cookies
     * @magentoApiConfigFixture default_store web/default/show_cms_breadcrumbs 0
     */
    public function testGetDefaultStoreCmsConfig()
    {
        $expectedConfigs = [
            'front' => 'Default Store Front',
            'cms_home_page' => 'Default Store Homepage',
            'no_route' => 'default_store_no_route',
            'cms_no_route' => 'default_store_cms_no_route',
            'cms_no_cookies' => 'default_store_cms_no_cookies',
            'show_cms_breadcrumbs' => 0
        ];

        $query
            = <<<QUERY
{
  storeConfig{
    front,
    cms_home_page,
    no_route,
    cms_no_route,
    cms_no_cookies,
    show_cms_breadcrumbs
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        $this->assertArrayHasKey('storeConfig', $response);
        foreach ($expectedConfigs as $key => $expectedConfigValue) {
            $this->assertEquals($expectedConfigValue, $response['storeConfig'][$key]);
        }
    }

    /**
     * @magentoApiDataFixture Magento/Store/_files/second_store.php
     * @magentoApiConfigFixture fixture_second_store_store web/default/front Test Store Front
     * @magentoApiConfigFixture fixture_second_store_store web/default/cms_home_page Test Store Homepage
     * @magentoApiConfigFixture fixture_second_store_store web/default/no_route test_store_no_route
     * @magentoApiConfigFixture fixture_second_store_store web/default/cms_no_route test_store_cms_no_route
     * @magentoApiConfigFixture fixture_second_store_store web/default/cms_no_cookies test_store_cms_no_cookies
     * @magentoApiConfigFixture fixture_second_store_store web/default/show_cms_breadcrumbs 1
     */
    public function testGetNotDefaultStoreCmsConfig()
    {
        $expectedConfigs = [
            'front' => 'Test Store Front',
            'cms_home_page' => 'Test Store Homepage',
            'no_route' => 'test_store_no_route',
            'cms_no_route' => 'test_store_cms_no_route',
            'cms_no_cookies' => 'test_store_cms_no_cookies',
            'show_cms_breadcrumbs' => 1
        ];

        $query
            = <<<QUERY
{
  storeConfig{
    front,
    cms_home_page,
    no_route,
    cms_no_route,
    cms_no_cookies,
    show_cms_breadcrumbs
  }
}
QUERY;
        $storeCodeFromFixture = 'fixture_second_store';
        $headerMap = ['Store' => $storeCodeFromFixture];
        $response = $this->graphQlQuery($query, [], '', $headerMap);

        $this->assertArrayHasKey('storeConfig', $response);
        foreach ($expectedConfigs as $key => $expectedConfigValue) {
            $this->assertEquals($expectedConfigValue, $response['storeConfig'][$key]);
        }
    }
}

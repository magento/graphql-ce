<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Theme;

use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test the GraphQL Theme related StoreConfigs query
 */
class ThemeConfigTest extends GraphQlAbstract
{
    /**
     * @magentoApiConfigFixture default_store design/head/head_shortcut_icon fixture_second_store_icon.ico
     * @magentoApiConfigFixture default_store design/head/default_title Default Store Title
     * @magentoApiConfigFixture default_store design/head/title_prefix Default Store Title Prefix
     * @magentoApiConfigFixture default_store design/head/title_suffix Default Store Suffix
     * @magentoApiConfigFixture default_store design/head/default_description Default Store Description
     * @magentoApiConfigFixture default_store design/head/default_keywords Default Store Keywords
     * @magentoApiConfigFixture default_store design/head/includes Default Store Scripts And Stylesheets
     * @magentoApiConfigFixture default_store design/head/demonotice 1
     * @magentoApiConfigFixture default_store design/header/header_logo_src fixture_second_store_logo.phg
     * @magentoApiConfigFixture default_store design/header/logo_width 200
     * @magentoApiConfigFixture default_store design/header/logo_height 300
     * @magentoApiConfigFixture default_store design/header/welcome Default Store Welcome
     * @magentoApiConfigFixture default_store design/header/logo_alt Default Store Alt
     * @magentoApiConfigFixture default_store design/footer/copyright Default Store Copyright
     * @magentoApiConfigFixture default_store design/footer/absolute_footer Default Store Footer
     */
    public function testGetDefaultStoreThemeConfig()
    {
        $expectedConfigs = [
            'head_shortcut_icon' => 'fixture_second_store_icon.ico',
            'default_title' => 'Default Store Title',
            'title_prefix' => 'Default Store Title Prefix',
            'title_suffix' => 'Default Store Suffix',
            'default_description' => 'Default Store Description',
            'default_keywords' => 'Default Store Keywords',
            'head_includes' => 'Default Store Scripts And Stylesheets',
            'demonotice' => 1,
            'header_logo_src' => 'fixture_second_store_logo.phg',
            'logo_width' => 200,
            'logo_height' => 300,
            'welcome' => 'Default Store Welcome',
            'logo_alt' => 'Default Store Alt',
            'copyright' => 'Default Store Copyright',
            'absolute_footer' => 'Default Store Footer'
        ];

        $query
            = <<<QUERY
{
  storeConfig{
    head_shortcut_icon,
    default_title,
    title_prefix,
    title_suffix,
    default_description,
    default_keywords,
    head_includes,
    demonotice,
    header_logo_src,
    logo_width,
    logo_height,
    welcome,
    logo_alt,
    absolute_footer,
    copyright
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
     * @magentoApiConfigFixture fixture_second_store_store design/head/head_shortcut_icon test_store_icon.ico
     * @magentoApiConfigFixture fixture_second_store_store design/head/default_title Test Store Title
     * @magentoApiConfigFixture fixture_second_store_store design/head/title_prefix Test Store Title Prefix
     * @magentoApiConfigFixture fixture_second_store_store design/head/title_suffix Test Store Suffix
     * @magentoApiConfigFixture fixture_second_store_store design/head/default_description Test Store Description
     * @magentoApiConfigFixture fixture_second_store_store design/head/default_keywords Test Store Keywords
     * @magentoApiConfigFixture fixture_second_store_store design/head/includes Test Store Scripts And Stylesheets
     * @magentoApiConfigFixture fixture_second_store_store design/head/demonotice 0
     * @magentoApiConfigFixture fixture_second_store_store design/header/header_logo_src test_store_logo.phg
     * @magentoApiConfigFixture fixture_second_store_store design/header/logo_width 250
     * @magentoApiConfigFixture fixture_second_store_store design/header/logo_height 350
     * @magentoApiConfigFixture fixture_second_store_store design/header/welcome Test Store Welcome
     * @magentoApiConfigFixture fixture_second_store_store design/header/logo_alt Test Store Alt
     * @magentoApiConfigFixture fixture_second_store_store design/footer/copyright Test Store Copyright
     * @magentoApiConfigFixture fixture_second_store_store design/footer/absolute_footer Test Store Footer
     */
    public function testGetNotDefaultStoreThemeConfig()
    {
        $expectedConfigs = [
            'head_shortcut_icon' => 'test_store_icon.ico',
            'default_title' => 'Test Store Title',
            'title_prefix' => 'Test Store Title Prefix',
            'title_suffix' => 'Test Store Suffix',
            'default_description' => 'Test Store Description',
            'default_keywords' => 'Test Store Keywords',
            'head_includes' => 'Test Store Scripts And Stylesheets',
            'demonotice' => 0,
            'header_logo_src' => 'test_store_logo.phg',
            'logo_width' => 250,
            'logo_height' => 350,
            'welcome' => 'Test Store Welcome',
            'logo_alt' => 'Test Store Alt',
            'copyright' => 'Test Store Copyright',
            'absolute_footer' => 'Test Store Footer'
        ];

        $query
            = <<<QUERY
{
  storeConfig{
    head_shortcut_icon,
    default_title,
    title_prefix,
    title_suffix,
    default_description,
    default_keywords,
    head_includes,
    demonotice,
    header_logo_src,
    logo_width,
    logo_height,
    welcome,
    logo_alt,
    absolute_footer,
    copyright
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

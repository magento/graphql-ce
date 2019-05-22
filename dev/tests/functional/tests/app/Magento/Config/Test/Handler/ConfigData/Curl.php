<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Config\Test\Handler\ConfigData;

use Magento\Config\Test\Fixture\ConfigData\Section;
use Magento\Mtf\Fixture\FixtureInterface;
use Magento\Mtf\Handler\Curl as AbstractCurl;
use Magento\Mtf\Util\Protocol\CurlTransport;
use Magento\Mtf\Util\Protocol\CurlTransport\BackendDecorator;
use Magento\Store\Test\Fixture\Store;
use Magento\Store\Test\Fixture\Website;

/**
 * Setting config.
 */
class Curl extends AbstractCurl implements ConfigDataInterface
{
    /**
     * FixtureInterface object.
     *
     * @var FixtureInterface
     */
    private $fixture;

    /**
     * Mapping values for data.
     *
     * @var array
     */
    protected $mappingData = [
        'scope' => [
            'Website' => 'website',
            'Store' => 'group',
            'Store View' => 'store',
        ],
    ];

    /**
     * Post request for setting configuration.
     *
     * @param FixtureInterface|null $fixture [optional]
     * @return void
     */
    public function persist(FixtureInterface $fixture = null)
    {
        $this->fixture = $fixture;
        $data = $this->prepareData($fixture);
        foreach ($data as $scope => $item) {
            $this->applyConfigSettings($item, $scope);
        }
    }

    /**
     * Prepare POST data for setting configuration.
     *
     * @param FixtureInterface $fixture
     * @return array
     */
    protected function prepareData(FixtureInterface $fixture)
    {
        $configPath = [];
        $result = [];
        $fields = $fixture->getData();
        if (isset($fields['section'])) {
            foreach ($fields['section'] as $key => $itemSection) {
                if (is_array($itemSection)) {
                    $itemSection['path'] = $key;
                }
                parse_str($this->prepareConfigPath($itemSection), $configPath);
                $result = array_merge_recursive($result, $configPath);
            }
        }
        return $result;
    }

    /**
     * Prepare config path.
     *
     * From payment/cashondelivery/active to ['payment']['groups']['cashondelivery']['fields']['active']
     *
     * @param array $input
     * @return string
     */
    protected function prepareConfigPath(array $input)
    {
        $resultArray = '';
        $path = explode('/', $input['path']);
        foreach ($path as $position => $subPath) {
            if ($position === 0) {
                $resultArray .= $subPath;
                continue;
            } elseif ($position === (count($path) - 1)) {
                $resultArray .= '[fields]';
            } else {
                $resultArray .= '[groups]';
            }
            $resultArray .= '[' . $subPath . ']';
        }
        $valueCode = isset($input['inherit']) ? 'inherit' : 'value';
        $resultArray .= "[$valueCode]";
        if (isset($input['value']) && is_array($input['value'])) {
            $values = [];
            foreach ($input['value'] as $key => $value) {
                $values[] = $resultArray . "[$key]=$value";
            }
            $resultArray = implode('&', $values);
        } else {
            $resultArray .= '=' . $input[$valueCode];
        }
        return $resultArray;
    }

    /**
     * Apply config settings via curl.
     *
     * @param array $data
     * @param string $section
     * @throws \Exception
     */
    protected function applyConfigSettings(array $data, $section)
    {
        $curl = new BackendDecorator(new CurlTransport(), $this->_configuration);
        $curl->addOption(CURLOPT_HEADER, 1);
        $url = $this->getUrl($section);
        $curl->write($url, $data);
        $response = $curl->read();
        $curl->close();

        if (strpos($response, 'data-ui-id="messages-message-success"') === false) {
            $this->_eventManager->dispatchEvent(['curl_failed'], [$response]);
            throw new \Exception(
                "Configuration settings are not applied! Url: $url" . PHP_EOL . "data: " . print_r($data, true)
            );
        }
    }

    /**
     * Retrieve URL for request.
     *
     * @param string $section
     * @return string
     */
    protected function getUrl($section)
    {
        return $_ENV['app_backend_url'] . 'admin/system_config/save/section/' . $section . $this->getStoreViewUrl();
    }

    /**
     * Get store view url.
     *
     * @return string
     */
    private function getStoreViewUrl()
    {
        $result = '';
        /** @var Section $source */
        $source = $this->fixture->getDataFieldConfig('section')['source'];
        /** @var Store|Website $scope */
        $scope = $source->getScope();
        if ($scope !== null) {
            $code = $source->getScopeType();
            $result = $code . '/' . $scope->getData($code . '_id');
        }

        return $result ? '/' . $result : '';
    }
}

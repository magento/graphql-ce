<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Catalog\Test\Handler\CatalogProductAttribute;

use Magento\Mtf\Fixture\FixtureInterface;
use Magento\Mtf\Handler\Curl as AbstractCurl;
use Magento\Mtf\Util\Protocol\CurlTransport;
use Magento\Mtf\Util\Protocol\CurlTransport\BackendDecorator;

/**
 * Class Curl
 * Create new Product Attribute via curl
 */
class Curl extends AbstractCurl implements CatalogProductAttributeInterface
{
    /**
     * Relative action path with parameters.
     *
     * @var string
     */
    protected $urlActionPath = 'catalog/product_attribute/save/back/edit';

    /**
     * Message for Exception when was received not successful response.
     *
     * @var string
     */
    protected $responseExceptionMessage = 'Product Attribute creating by curl handler was not successful!';

    /**
     * Mapping values for data.
     *
     * @var array
     */
    protected $mappingData = [
        'frontend_input' => [
            'Text Field' => 'text',
            'Text Area' => 'textarea',
            'Date' => 'date',
            'Yes/No' => 'boolean',
            'Multiple Select' => 'multiselect',
            'Dropdown' => 'select',
            'Price' => 'price',
            'Media Image' => 'media_image',
            'Fixed Product Tax' => 'weee',
        ],
        'is_required' => [
            'Yes' => 1,
            'No' => 0,
        ],
        'is_searchable' => [
            'Yes' => 1,
            'No' => 0,
        ],
        'is_filterable' => [
            'No' => 0,
            'Filterable (with results)' => 1,
            'Filterable (no results)' => 2
        ],
        'is_used_for_promo_rules' => [
            'No' => 0,
            'Yes' => 1,
        ],
        'is_global' => [
            'Store View' => '0',
            'Global' => '1',
        ],
        'used_in_product_listing' => [
            'No' => '0',
            'Yes' => '1',
        ],
    ];

    /**
     * Post request for creating Product Attribute
     *
     * @param FixtureInterface|null $fixture [optional]
     * @return array
     * @throws \Exception
     */
    public function persist(FixtureInterface $fixture = null)
    {
        if ($fixture->hasData('attribute_id')) {
            return ['attribute_id' => $fixture->getData('attribute_id')];
        }
        $data = $this->replaceMappingData($fixture->getData());
        $data['frontend_label'] = [0 => $data['frontend_label']];

        if (isset($data['options'])) {
            $optionsData = [];
            foreach ($data['options'] as $key => $values) {
                $optionRowData = [];
                $index = 'option_' . $key;
                if ($values['is_default'] == 'Yes') {
                    $optionRowData['default'][] = $index;
                }
                $optionRowData['option']['value'][$index] = [$values['admin'], $values['view']];
                $optionRowData['option']['order'][$index] = $key;
                $optionsData[] = $optionRowData;
            }
            $data['options'] = $optionsData;
        }

        $data = $this->changeStructureOfTheData($data);
        $url = $_ENV['app_backend_url'] . $this->urlActionPath;
        $curl = new BackendDecorator(new CurlTransport(), $this->_configuration);
        $curl->write($url, $data);
        $response = $curl->read();
        $curl->close();

        if (strpos($response, 'data-ui-id="messages-message-success"') === false) {
            $this->_eventManager->dispatchEvent(['curl_failed'], [$response]);
            throw new \Exception($this->responseExceptionMessage);
        }

        $resultData = [];
        $matches = [];
        preg_match('#attribute_id[^>]+value="(\d+)"#', $response, $matches);
        $resultData['attribute_id'] = $matches[1];

        $matches = [];
        preg_match_all('#"id":"(\d+)"#Umi', $response, $matches);

        if ($fixture->hasData('options')) {
            $optionsData = $fixture->getData()['options'];
            foreach (array_unique($matches[1]) as $key => $optionId) {
                $optionsData[$key]['id'] = $optionId;
            }
            $resultData['options'] = $optionsData;
        }

        return $resultData;
    }

    /**
     * Additional data handling.
     *
     * @param array $data
     * @return array
     */
    protected function changeStructureOfTheData(array $data): array
    {
        if (!isset($data['options'])) {
            return $data;
        }

        $serializedOptions = $this->getSerializeOptions($data['options']);
        if ($serializedOptions) {
            $data['serialized_options'] = $serializedOptions;
            unset($data['options']);
        }

        return $data;
    }

    /**
     * Provides serialized product attribute options.
     *
     * @param array $data
     * @return string
     */
    protected function getSerializeOptions(array $data): string
    {
        $options = [];
        foreach ($data as $optionRowData) {
            $options[] = http_build_query($optionRowData);
        }

        return json_encode($options);
    }
}

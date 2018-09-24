<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Elasticsearch\SearchAdapter;

use Magento\Elasticsearch\Model\Config;

/**
 * Class AdapterTest
 *
 * @magentoDbIsolation disabled
 * @magentoDataFixture Magento/Framework/Search/_files/products.php
 *
 * Important: Please make sure that each integration test file works with unique elastic search index. In order to
 * achieve this, use @ magentoConfigFixture to pass unique value for 'elasticsearch_index_prefix' for every test
 * method. E.g. '@ magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest'
 *
 * In ElasticSearch, a reindex is required if the test includes a new data fixture with new items to search, see
 * testAdvancedSearchDateField().
 *
 */
class AdapterTest extends \Magento\Framework\Search\Adapter\Mysql\AdapterTest
{
    /**
     * @var string
     */
    protected $searchEngine = Config::ENGINE_NAME;

    /**
     * Get request config path
     *
     * @return string
     */
    protected function getRequestConfigPath()
    {
        return __DIR__ . '/../_files/requests.xml';
    }

    /**
     * @return \Magento\Framework\Search\AdapterInterface
     */
    protected function createAdapter()
    {
        return $this->objectManager->create(\Magento\Elasticsearch\SearchAdapter\Adapter::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testMatchQuery()
    {
        parent::testMatchQuery();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testMatchOrderedQuery()
    {
        $this->markTestSkipped(
            'Elasticsearch not expected to order results by default. Test is skipped intentionally.'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testAggregationsQuery()
    {
        $this->markTestSkipped('Range query is not supported. Test is skipped intentionally.');
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testMatchQueryFilters()
    {
        parent::testMatchQueryFilters();
    }

    /**
     * Range filter test with all fields filled
     *
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testRangeFilterWithAllFields()
    {
        parent::testRangeFilterWithAllFields();
    }

    /**
     * Range filter test with all fields filled
     *
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testRangeFilterWithoutFromField()
    {
        parent::testRangeFilterWithoutFromField();
    }

    /**
     * Range filter test with all fields filled
     *
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testRangeFilterWithoutToField()
    {
        parent::testRangeFilterWithoutToField();
    }

    /**
     * Term filter test
     *
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testTermFilter()
    {
        parent::testTermFilter();
    }

    /**
     * Term filter test
     *
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testTermFilterArray()
    {
        parent::testTermFilterArray();
    }

    /**
     * Term filter test
     *
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testWildcardFilter()
    {
        parent::testWildcardFilter();
    }

    /**
     * Request limits test
     *
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testSearchLimit()
    {
        parent::testSearchLimit();
    }

    /**
     * Bool filter test
     *
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testBoolFilter()
    {
        parent::testBoolFilter();
    }

    /**
     * Test bool filter with nested negative bool filter
     *
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testBoolFilterWithNestedNegativeBoolFilter()
    {
        parent::testBoolFilterWithNestedNegativeBoolFilter();
    }

    /**
     * Test range inside nested negative bool filter
     *
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testBoolFilterWithNestedRangeInNegativeBoolFilter()
    {
        parent::testBoolFilterWithNestedRangeInNegativeBoolFilter();
    }

    /**
     * Sample Advanced search request test
     *
     * @dataProvider elasticSearchAdvancedSearchDataProvider
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     * @param string $nameQuery
     * @param string $descriptionQuery
     * @param array $rangeFilter
     * @param int $expectedRecordsCount
     */
    public function testSimpleAdvancedSearch(
        $nameQuery,
        $descriptionQuery,
        $rangeFilter,
        $expectedRecordsCount
    ) {
        parent::testSimpleAdvancedSearch(
            $nameQuery,
            $descriptionQuery,
            $rangeFilter,
            $expectedRecordsCount
        );
    }

    /**
     * Elastic Search specific data provider for advanced search test.
     *
     * The expected array is for Elastic Search is different that the one for MySQL
     * because sometimes more matches are returned. For instance, 3rd index below
     * will return 3 matches instead of 1 (which is what MySQL returns).
     *
     * @return array
     */
    public function elasticSearchAdvancedSearchDataProvider()
    {
        return [
            ['white', 'shorts', ['from' => '16', 'to' => '18'], 0],
            ['white', 'shorts',['from' => '12', 'to' => '18'], 1],
            ['black', 'tshirts', ['from' => '12', 'to' => '20'], 0],
            ['shorts', 'green', ['from' => '12', 'to' => '22'], 3],
            //Search with empty fields/values
            ['white', '  ', ['from' => '12', 'to' => '22'], 1],
            ['  ', 'green', ['from' => '12', 'to' => '22'], 2]
        ];
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Framework/Search/_files/filterable_attribute.php
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testCustomFilterableAttribute()
    {
        // Reindex Elastic Search since filterable_attribute data fixture added new fields to be indexed
        $this->reindexAll();
        parent::testCustomFilterableAttribute();
    }

    /**
     * Test filtering by two attributes.
     *
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Framework/Search/_files/filterable_attributes.php
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     * @dataProvider filterByAttributeValuesDataProvider
     * @param string $requestName
     * @param array $additionalData
     * @return void
     */
    public function testFilterByAttributeValues($requestName, $additionalData)
    {
        // Reindex Elastic Search since filterable_attribute data fixture added new fields to be indexed
        $this->reindexAll();
        parent::testFilterByAttributeValues($requestName, $additionalData);
    }

    /**
     * Advanced search request using date product attribute
     *
     * @param $rangeFilter
     * @param $expectedRecordsCount
     * @magentoDataFixture Magento/Framework/Search/_files/date_attribute.php
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     * @magentoAppIsolation enabled
     * @dataProvider dateDataProvider
     */
    public function testAdvancedSearchDateField($rangeFilter, $expectedRecordsCount)
    {
        // Reindex Elastic Search since date_attribute data fixture added new fields to be indexed
        $this->reindexAll();
        parent::testAdvancedSearchDateField($rangeFilter, $expectedRecordsCount);
    }

    /**
     * @magentoDataFixture Magento/Framework/Search/_files/product_configurable.php
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testAdvancedSearchCompositeProductWithOutOfStockOption()
    {
        parent::testAdvancedSearchCompositeProductWithOutOfStockOption();
    }

    /**
     * @magentoDataFixture Magento/Framework/Search/_files/product_configurable_with_disabled_child.php
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testAdvancedSearchCompositeProductWithDisabledChild()
    {
        // Reindex Elastic Search since date_attribute data fixture added new fields to be indexed
        $this->reindexAll();
        parent::testAdvancedSearchCompositeProductWithDisabledChild();
    }

    /**
     * @magentoDataFixture Magento/Framework/Search/_files/search_weight_products.php
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/catalog/search/engine elasticsearch
     * @magentoConfigFixture current_store catalog/search/elasticsearch_index_prefix adaptertest
     */
    public function testSearchQueryBoost()
    {
        // Reindex Elastic Search since date_attribute data fixture added new fields to be indexed
        $this->reindexAll();
        parent::testSearchQueryBoost();
    }

    /**
     * Perform full reindex
     *
     * @return void
     */
    private function reindexAll()
    {
        $indexer = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Indexer\Model\Indexer::class
        );
        $indexer->load('catalogsearch_fulltext');
        $indexer->reindexAll();
    }
}

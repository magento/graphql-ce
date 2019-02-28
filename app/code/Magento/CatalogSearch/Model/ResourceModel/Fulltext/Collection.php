<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogSearch\Model\ResourceModel\Fulltext;

use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Catalog\Model\Indexer\Product\Price\PriceTableResolver;
use Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitationFactory;
use Magento\CatalogSearch\Model\Search\RequestGenerator;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Indexer\DimensionFactory;
use Magento\Framework\Model\ResourceModel\ResourceModelPoolInterface;
use Magento\Framework\Search\Adapter\Mysql\TemporaryStorage;
use Magento\Framework\Search\Request\EmptyRequestDataException;
use Magento\Framework\Search\Request\NonExistingRequestNameException;
use Magento\Framework\Search\Response\QueryResponse;

/**
 * Fulltext Collection
 *
 * This collection should be refactored to not have dependencies on MySQL-specific implementation.
 *
 * @api
 * @since 100.0.2
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    /**
     * @var  QueryResponse
     * @deprecated 100.1.0
     */
    protected $queryResponse;

    /**
     * Catalog search data
     *
     * @var \Magento\Search\Model\QueryFactory
     * @deprecated 100.1.0
     */
    protected $queryFactory = null;

    /**
     * @var \Magento\Framework\Search\Request\Builder
     * @deprecated 100.1.0
     */
    private $requestBuilder;

    /**
     * @var \Magento\Search\Model\SearchEngine
     * @deprecated 100.1.0
     */
    private $searchEngine;

    /**
     * @var string
     */
    private $queryText;

    /**
     * @var string|null
     */
    private $relevanceOrderDirection = null;

    /**
     * @var string
     */
    private $searchRequestName;

    /**
     * @var \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory
     * @deprecated There must be no dependencies on specific adapter in generic search implementation
     */
    private $temporaryStorageFactory;

    /**
     * @var \Magento\Search\Api\SearchInterface
     */
    private $search;

    /**
     * @var \Magento\Framework\Api\Search\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\Search\SearchResultInterface
     */
    private $searchResult;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    private $filterBuilder;

    /**
     * Collection constructor
     *
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Eav\Model\EntityFactory $eavEntityFactory
     * @param \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Validator\UniversalFactory $universalFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Url $catalogUrl
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Customer\Api\GroupManagementInterface $groupManagement
     * @param \Magento\Search\Model\QueryFactory $catalogSearchData
     * @param \Magento\Framework\Search\Request\Builder $requestBuilder
     * @param \Magento\Search\Model\SearchEngine $searchEngine
     * @param \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory $temporaryStorageFactory
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param string $searchRequestName
     * @param SearchResultFactory|null $searchResultFactory
     * @param ProductLimitationFactory|null $productLimitationFactory
     * @param MetadataPool|null $metadataPool
     * @param TableMaintainer|null $tableMaintainer
     * @param PriceTableResolver|null $priceTableResolver
     * @param DimensionFactory|null $dimensionFactory
     * @param ResourceModelPoolInterface|null $resourceModelPool
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Magento\Search\Model\QueryFactory $catalogSearchData,
        \Magento\Framework\Search\Request\Builder $requestBuilder,
        \Magento\Search\Model\SearchEngine $searchEngine,
        \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory $temporaryStorageFactory,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        $searchRequestName = 'catalog_view_container',
        SearchResultFactory $searchResultFactory = null,
        ProductLimitationFactory $productLimitationFactory = null,
        MetadataPool $metadataPool = null,
        TableMaintainer $tableMaintainer = null,
        PriceTableResolver $priceTableResolver = null,
        DimensionFactory $dimensionFactory = null,
        ResourceModelPoolInterface $resourceModelPool = null
    ) {
        $this->queryFactory = $catalogSearchData;
        if ($searchResultFactory === null) {
            $this->searchResultFactory = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Api\Search\SearchResultFactory::class);
        }
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $moduleManager,
            $catalogProductFlatState,
            $scopeConfig,
            $productOptionFactory,
            $catalogUrl,
            $localeDate,
            $customerSession,
            $dateTime,
            $groupManagement,
            $connection,
            $productLimitationFactory,
            $metadataPool,
            $tableMaintainer,
            $priceTableResolver,
            $dimensionFactory,
            $resourceModelPool
        );
        $this->requestBuilder = $requestBuilder;
        $this->searchEngine = $searchEngine;
        $this->temporaryStorageFactory = $temporaryStorageFactory;
        $this->searchRequestName = $searchRequestName;
    }

    /**
     * Get search.
     *
     * @deprecated 100.1.0
     * @return \Magento\Search\Api\SearchInterface
     */
    private function getSearch()
    {
        if ($this->search === null) {
            $this->search = ObjectManager::getInstance()->get(\Magento\Search\Api\SearchInterface::class);
        }
        return $this->search;
    }

    /**
     * Test search.
     *
     * @deprecated 100.1.0
     * @param \Magento\Search\Api\SearchInterface $object
     * @return void
     * @since 100.1.0
     */
    public function setSearch(\Magento\Search\Api\SearchInterface $object)
    {
        $this->search = $object;
    }

    /**
     * Set search criteria builder.
     *
     * @deprecated 100.1.0
     * @return \Magento\Framework\Api\Search\SearchCriteriaBuilder
     */
    private function getSearchCriteriaBuilder()
    {
        if ($this->searchCriteriaBuilder === null) {
            $this->searchCriteriaBuilder = ObjectManager::getInstance()
                ->get(\Magento\Framework\Api\Search\SearchCriteriaBuilder::class);
        }
        return $this->searchCriteriaBuilder;
    }

    /**
     * Set search criteria builder.
     *
     * @deprecated 100.1.0
     * @param \Magento\Framework\Api\Search\SearchCriteriaBuilder $object
     * @return void
     * @since 100.1.0
     */
    public function setSearchCriteriaBuilder(\Magento\Framework\Api\Search\SearchCriteriaBuilder $object)
    {
        $this->searchCriteriaBuilder = $object;
    }

    /**
     * Get filter builder.
     *
     * @deprecated 100.1.0
     * @return \Magento\Framework\Api\FilterBuilder
     */
    private function getFilterBuilder()
    {
        if ($this->filterBuilder === null) {
            $this->filterBuilder = ObjectManager::getInstance()->get(\Magento\Framework\Api\FilterBuilder::class);
        }
        return $this->filterBuilder;
    }

    /**
     * Set filter builder.
     *
     * @deprecated 100.1.0
     * @param \Magento\Framework\Api\FilterBuilder $object
     * @return void
     * @since 100.1.0
     */
    public function setFilterBuilder(\Magento\Framework\Api\FilterBuilder $object)
    {
        $this->filterBuilder = $object;
    }

    /**
     * Apply attribute filter to facet collection
     *
     * @param string $field
     * @param mixed|null $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($this->searchResult !== null) {
            throw new \RuntimeException('Illegal state');
        }

        $this->getSearchCriteriaBuilder();
        $this->getFilterBuilder();
        if (!is_array($condition) || !in_array(key($condition), ['from', 'to'], true)) {
            $this->filterBuilder->setField($field);
            $this->filterBuilder->setValue($condition);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        } else {
            if (!empty($condition['from'])) {
                $this->filterBuilder->setField("{$field}.from");
                $this->filterBuilder->setValue($condition['from']);
                $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
            }
            if (!empty($condition['to'])) {
                $this->filterBuilder->setField("{$field}.to");
                $this->filterBuilder->setValue($condition['to']);
                $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
            }
        }
        return $this;
    }

    /**
     * Add search query filter
     *
     * @param string $query
     * @return $this
     */
    public function addSearchFilter($query)
    {
        $this->queryText = trim($this->queryText . ' ' . $query);
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _renderFiltersBefore()
    {
        $this->getSearchCriteriaBuilder();
        $this->getFilterBuilder();
        $this->getSearch();

        if ($this->queryText) {
            $this->filterBuilder->setField('search_term');
            $this->filterBuilder->setValue($this->queryText);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        }

        $priceRangeCalculation = $this->_scopeConfig->getValue(
            \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory::XML_PATH_RANGE_CALCULATION,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($priceRangeCalculation) {
            $this->filterBuilder->setField('price_dynamic_algorithm');
            $this->filterBuilder->setValue($priceRangeCalculation);
            $this->searchCriteriaBuilder->addFilter($this->filterBuilder->create());
        }

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchCriteria->setRequestName($this->searchRequestName);
        try {
            $this->searchResult = $this->getSearch()->search($searchCriteria);
        } catch (EmptyRequestDataException $e) {
            /** @var \Magento\Framework\Api\Search\SearchResultInterface $searchResult */
            $this->searchResult = $this->searchResultFactory->create()->setItems([]);
        } catch (NonExistingRequestNameException $e) {
            $this->_logger->error($e->getMessage());
            throw new LocalizedException(__('An error occurred. For details, see the error log.'));
        }

        $temporaryStorage = $this->temporaryStorageFactory->create();
        $table = $temporaryStorage->storeApiDocuments($this->searchResult->getItems());

        $this->getSelect()->joinInner(
            [
                'search_result' => $table->getName(),
            ],
            'e.entity_id = search_result.' . TemporaryStorage::FIELD_ENTITY_ID,
            []
        );

        if ($this->relevanceOrderDirection) {
            $this->getSelect()->order(
                'search_result.' . TemporaryStorage::FIELD_SCORE . ' ' . $this->relevanceOrderDirection
            );
        }
        return parent::_renderFiltersBefore();
    }

    /**
     * @inheritdoc
     */
    protected function _beforeLoad()
    {
        /*
         * This order is required to force search results be the same
         * for the same requests and products with the same relevance
         * NOTE: this does not replace existing orders but ADDs one more
         */
        $this->setOrder('entity_id');
        return parent::_beforeLoad();
    }

    /**
     * Render filters.
     *
     * @return $this
     */
    protected function _renderFilters()
    {
        $this->_filters = [];
        return parent::_renderFilters();
    }

    /**
     * Set Order field
     *
     * @param string $attribute
     * @param string $dir
     * @return $this
     */
    public function setOrder($attribute, $dir = Select::SQL_DESC)
    {
        if ($attribute === 'relevance') {
            $this->relevanceOrderDirection = $dir;
        } else {
            parent::setOrder($attribute, $dir);
        }

        return $this;
    }

    /**
     * Stub method for compatibility with other search engines
     *
     * @return $this
     */
    public function setGeneralDefaultQuery()
    {
        return $this;
    }

    /**
     * Return field faceted data from faceted search result
     *
     * @param string $field
     * @return array
     * @throws StateException
     */
    public function getFacetedData($field)
    {
        $this->_renderFilters();
        $result = [];
        $aggregations = $this->searchResult->getAggregations();
        // This behavior is for case with empty object when we got EmptyRequestDataException
        if (null !== $aggregations) {
            $bucket = $aggregations->getBucket($field . RequestGenerator::BUCKET_SUFFIX);
            if ($bucket) {
                foreach ($bucket->getValues() as $value) {
                    $metrics = $value->getMetrics();
                    $result[$metrics['value']] = $metrics;
                }
            } else {
                throw new StateException(__("The bucket doesn't exist."));
            }
        }
        return $result;
    }

    /**
     * Specify category filter for product collection
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return $this
     */
    public function addCategoryFilter(\Magento\Catalog\Model\Category $category)
    {
        $this->addFieldToFilter('category_ids', $category->getId());
        return parent::addCategoryFilter($category);
    }

    /**
     * Set product visibility filter for enabled products
     *
     * @param array $visibility
     * @return $this
     */
    public function setVisibility($visibility)
    {
        $this->addFieldToFilter('visibility', $visibility);
        return parent::setVisibility($visibility);
    }
}

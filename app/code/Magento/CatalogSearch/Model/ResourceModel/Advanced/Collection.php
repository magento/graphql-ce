<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogSearch\Model\ResourceModel\Advanced;

use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Catalog\Model\Indexer\Product\Price\PriceTableResolver;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitationFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\DimensionFactory;
use Magento\Framework\Model\ResourceModel\ResourceModelPoolInterface;
use Magento\Framework\Search\Adapter\Mysql\TemporaryStorage;
use Magento\Framework\Search\Request\EmptyRequestDataException;
use Magento\Framework\Search\Request\NonExistingRequestNameException;

/**
 * Advanced search collection
 *
 * This collection should be refactored to not have dependencies on MySQL-specific implementation.
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    /**
     * List Of filters
     * @var array
     */
    private $filters = [];

    /**
     * @var \Magento\Search\Api\SearchInterface
     */
    private $search;

    /**
     * @var \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory
     * @deprecated There must be no dependencies on specific adapter in generic search implementation
     */
    private $temporaryStorageFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var FilterBuilder
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
     * @param Product\OptionFactory $productOptionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Url $catalogUrl
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Customer\Api\GroupManagementInterface $groupManagement
     * @param \Magento\CatalogSearch\Model\Advanced\Request\Builder $requestBuilder
     * @param \Magento\Search\Model\SearchEngine $searchEngine
     * @param \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory $temporaryStorageFactory
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param SearchResultFactory|null $searchResultFactory
     * @param ProductLimitationFactory|null $productLimitationFactory
     * @param MetadataPool|null $metadataPool     *
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
        \Magento\CatalogSearch\Model\Advanced\Request\Builder $requestBuilder,
        \Magento\Search\Model\SearchEngine $searchEngine,
        \Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory $temporaryStorageFactory,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        SearchResultFactory $searchResultFactory = null,
        ProductLimitationFactory $productLimitationFactory = null,
        MetadataPool $metadataPool = null,
        TableMaintainer $tableMaintainer = null,
        PriceTableResolver $priceTableResolver = null,
        DimensionFactory $dimensionFactory = null,
        ResourceModelPoolInterface $resourceModelPool = null
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->searchEngine = $searchEngine;
        $this->temporaryStorageFactory = $temporaryStorageFactory;
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
    }

    /**
     * Add not indexable fields to search
     *
     * @param array $fields
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addFieldsToFilter($fields)
    {
        if ($fields) {
            $this->filters = array_merge($this->filters, $fields);
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _renderFiltersBefore()
    {
        if ($this->filters) {
            foreach ($this->filters as $attributes) {
                foreach ($attributes as $attributeCode => $attributeValue) {
                    $attributeCode = $this->getAttributeCode($attributeCode);
                    $this->addAttributeToSearch($attributeCode, $attributeValue);
                }
            }
            $searchCriteria = $this->getSearchCriteriaBuilder()->create();
            $searchCriteria->setRequestName('advanced_search_container');
            try {
                $searchResult = $this->getSearch()->search($searchCriteria);
            } catch (EmptyRequestDataException $e) {
                /** @var \Magento\Framework\Api\Search\SearchResultInterface $searchResult */
                $searchResult = $this->searchResultFactory->create()->setItems([]);
            } catch (NonExistingRequestNameException $e) {
                $this->_logger->error($e->getMessage());
                throw new LocalizedException(
                    __('An error occurred. For details, see the error log.')
                );
            }
            $temporaryStorage = $this->temporaryStorageFactory->create();
            $table = $temporaryStorage->storeApiDocuments($searchResult->getItems());

            $this->getSelect()->joinInner(
                [
                    'search_result' => $table->getName(),
                ],
                'e.entity_id = search_result.' . TemporaryStorage::FIELD_ENTITY_ID,
                []
            );
        }
        parent::_renderFiltersBefore();
    }

    /**
     * Get attribute code.
     *
     * @param string $attributeCode
     * @return string
     */
    private function getAttributeCode($attributeCode)
    {
        if (is_numeric($attributeCode)) {
            $attributeCode = $this->_eavConfig->getAttribute(Product::ENTITY, $attributeCode)
                ->getAttributeCode();
        }

        return $attributeCode;
    }

    /**
     * Create a filter and add it to the SearchCriteriaBuilder.
     *
     * @param string $attributeCode
     * @param array|string $attributeValue
     * @return void
     */
    private function addAttributeToSearch($attributeCode, $attributeValue)
    {
        if (isset($attributeValue['from']) || isset($attributeValue['to'])) {
            $this->addRangeAttributeToSearch($attributeCode, $attributeValue);
        } elseif (!is_array($attributeValue)) {
            $this->getFilterBuilder()->setField($attributeCode)->setValue($attributeValue);
            $this->getSearchCriteriaBuilder()->addFilter($this->getFilterBuilder()->create());
        } elseif (isset($attributeValue['like'])) {
            $this->getFilterBuilder()->setField($attributeCode)->setValue($attributeValue['like']);
            $this->getSearchCriteriaBuilder()->addFilter($this->getFilterBuilder()->create());
        } elseif (isset($attributeValue['in'])) {
            $this->getFilterBuilder()->setField($attributeCode)->setValue($attributeValue['in']);
            $this->getSearchCriteriaBuilder()->addFilter($this->getFilterBuilder()->create());
        } elseif (isset($attributeValue['in_set'])) {
            $this->getFilterBuilder()->setField($attributeCode)->setValue($attributeValue['in_set']);
            $this->getSearchCriteriaBuilder()->addFilter($this->getFilterBuilder()->create());
        }
    }

    /**
     * Add attributes that have a range (from,to) to the SearchCriteriaBuilder.
     *
     * @param string $attributeCode
     * @param array|string $attributeValue
     * @return void
     */
    private function addRangeAttributeToSearch($attributeCode, $attributeValue)
    {
        if (isset($attributeValue['from']) && '' !== $attributeValue['from']) {
            $this->getFilterBuilder()->setField("{$attributeCode}.from")->setValue($attributeValue['from']);
            $this->getSearchCriteriaBuilder()->addFilter($this->getFilterBuilder()->create());
        }
        if (isset($attributeValue['to']) && '' !== $attributeValue['to']) {
            $this->getFilterBuilder()->setField("{$attributeCode}.to")->setValue($attributeValue['to']);
            $this->getSearchCriteriaBuilder()->addFilter($this->getFilterBuilder()->create());
        }
    }

    /**
     * Get search.
     *
     * @return \Magento\Search\Api\SearchInterface
     */
    private function getSearch()
    {
        if (null === $this->search) {
            $this->search = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Search\Api\SearchInterface::class);
        }
        return $this->search;
    }

    /**
     * Get search criteria builder.
     *
     * @return SearchCriteriaBuilder
     */
    private function getSearchCriteriaBuilder()
    {
        if (null === $this->searchCriteriaBuilder) {
            $this->searchCriteriaBuilder = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Api\Search\SearchCriteriaBuilder::class);
        }
        return $this->searchCriteriaBuilder;
    }

    /**
     * Get filter builder.
     *
     * @return FilterBuilder
     */
    private function getFilterBuilder()
    {
        if (null === $this->filterBuilder) {
            $this->filterBuilder = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Api\FilterBuilder::class);
        }
        return $this->filterBuilder;
    }
}

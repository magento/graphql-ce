<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model\Indexer\Product\Eav\Action;

use Magento\Catalog\Model\ResourceModel\Indexer\ActiveTableSwitcher;

/**
 * Class Full reindex action
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Full extends \Magento\Catalog\Model\Indexer\Product\Eav\AbstractAction
{
    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;

    /**
     * @var \Magento\Framework\Indexer\BatchProviderInterface
     */
    private $batchProvider;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\BatchSizeCalculator
     */
    private $batchSizeCalculator;

    /**
     * @var ActiveTableSwitcher
     */
    private $activeTableSwitcher;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\DecimalFactory $eavDecimalFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\SourceFactory $eavSourceFactory
     * @param \Magento\Framework\EntityManager\MetadataPool|null $metadataPool
     * @param \Magento\Framework\Indexer\BatchProviderInterface|null $batchProvider
     * @param \Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\BatchSizeCalculator $batchSizeCalculator
     * @param ActiveTableSwitcher|null $activeTableSwitcher
     * @param \Magento\Framework\App\Config\ScopeConfigInterface|null $scopeConfig
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\DecimalFactory $eavDecimalFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\SourceFactory $eavSourceFactory,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool = null,
        \Magento\Framework\Indexer\BatchProviderInterface $batchProvider = null,
        \Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\BatchSizeCalculator $batchSizeCalculator = null,
        ActiveTableSwitcher $activeTableSwitcher = null,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig = null
    ) {
        $this->scopeConfig = $scopeConfig ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\App\Config\ScopeConfigInterface::class
        );
        parent::__construct($eavDecimalFactory, $eavSourceFactory, $scopeConfig);
        $this->metadataPool = $metadataPool ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\EntityManager\MetadataPool::class
        );
        $this->batchProvider = $batchProvider ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\Indexer\BatchProviderInterface::class
        );
        $this->batchSizeCalculator = $batchSizeCalculator ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\BatchSizeCalculator::class
        );
        $this->activeTableSwitcher = $activeTableSwitcher ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            ActiveTableSwitcher::class
        );
    }

    /**
     * Execute Full reindex
     *
     * @param array|int|null $ids
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($ids = null)
    {
        if (!$this->isEavIndexerEnabled()) {
            return;
        }
        try {
            foreach ($this->getIndexers() as $indexerName => $indexer) {
                $connection = $indexer->getConnection();
                $mainTable = $this->activeTableSwitcher->getAdditionalTableName($indexer->getMainTable());
                $connection->truncateTable($mainTable);
                $entityMetadata = $this->metadataPool->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class);
                $batches = $this->batchProvider->getBatches(
                    $connection,
                    $entityMetadata->getEntityTable(),
                    $entityMetadata->getIdentifierField(),
                    $this->batchSizeCalculator->estimateBatchSize($connection, $indexerName)
                );

                foreach ($batches as $batch) {
                    /** @var \Magento\Framework\DB\Select $select */
                    $select = $connection->select();
                    $select->distinct(true);
                    $select->from(['e' => $entityMetadata->getEntityTable()], $entityMetadata->getIdentifierField());
                    $entityIds = $this->batchProvider->getBatchIds($connection, $select, $batch);
                    if (!empty($entityIds)) {
                        $indexer->reindexEntities($this->processRelations($indexer, $entityIds, true));
                        $this->syncData($indexer, $mainTable);
                    }
                }
                $this->activeTableSwitcher->switchTable($indexer->getConnection(), [$indexer->getMainTable()]);
            }
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()), $e);
        }
    }

    /**
     * @inheritdoc
     */
    protected function syncData($indexer, $destinationTable, $ids = null)
    {
        $connection = $indexer->getConnection();
        $connection->beginTransaction();
        try {
            $sourceTable = $indexer->getIdxTable();
            $sourceColumns = array_keys($connection->describeTable($sourceTable));
            $targetColumns = array_keys($connection->describeTable($destinationTable));
            $select = $connection->select()->from($sourceTable, $sourceColumns);
            $query = $connection->insertFromSelect(
                $select,
                $destinationTable,
                $targetColumns,
                \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
            );
            $connection->query($query);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * Get EAV indexer status
     *
     * @return bool
     */
    private function isEavIndexerEnabled(): bool
    {
        $eavIndexerStatus = $this->scopeConfig->getValue(
            self::ENABLE_EAV_INDEXER,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return (bool)$eavIndexerStatus;
    }
}

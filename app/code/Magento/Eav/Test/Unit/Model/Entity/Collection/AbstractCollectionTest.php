<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eav\Test\Unit\Model\Entity\Collection;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Model\ResourceModel\ResourceModelPoolInterface;

/**
 * AbstractCollection test
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AbstractCollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AbstractCollectionStub|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $model;

    /**
     * @var \Magento\Framework\Data\Collection\EntityFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreEntityFactoryMock;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var FetchStrategyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fetchStrategyMock;

    /**
     * @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManagerMock;

    /**
     * @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreResourceMock;

    /**
     * @var \Magento\Eav\Model\EntityFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFactoryMock;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Helper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceHelperMock;

    /**
     * @var ResourceModelPoolInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceModelPoolMock;

    /**
     * @var \Magento\Framework\DB\Statement\Pdo\Mysql|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $statementMock;

    protected function setUp()
    {
        $this->coreEntityFactoryMock = $this->createMock(\Magento\Framework\Data\Collection\EntityFactory::class);
        $this->loggerMock = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->fetchStrategyMock = $this->createMock(FetchStrategyInterface::class);
        $this->eventManagerMock = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $this->configMock = $this->createMock(\Magento\Eav\Model\Config::class);
        $this->resourceHelperMock = $this->createMock(\Magento\Eav\Model\ResourceModel\Helper::class);
        $this->entityFactoryMock = $this->createMock(\Magento\Eav\Model\EntityFactory::class);
        $this->statementMock = $this->createPartialMock(\Magento\Framework\DB\Statement\Pdo\Mysql::class, ['fetch']);
        /** @var $selectMock \Magento\Framework\DB\Select|\PHPUnit_Framework_MockObject_MockObject */
        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->coreEntityFactoryMock->expects(
            $this->any()
        )->method(
            'create'
        )->will(
            $this->returnCallback([$this, 'getMagentoObject'])
        );
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject */
        $connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $connectionMock->expects($this->any())->method('select')->will($this->returnValue($selectMock));
        $connectionMock->expects($this->any())->method('query')->willReturn($this->statementMock);

        $this->coreResourceMock = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->coreResourceMock->expects(
            $this->any()
        )->method(
            'getConnection'
        )->will(
            $this->returnValue($connectionMock)
        );
        $entityMock = $this->createMock(\Magento\Eav\Model\Entity\AbstractEntity::class);
        $entityMock->expects($this->any())->method('getConnection')->will($this->returnValue($connectionMock));
        $entityMock->expects($this->any())->method('getDefaultAttributes')->will($this->returnValue([]));

        $this->resourceModelPoolMock = $this->createMock(ResourceModelPoolInterface::class);
        $this->resourceModelPoolMock->expects(
            $this->any()
        )->method(
            'get'
        )->with(
            'test_entity_model' // see \Magento\Eav\Test\Unit\Model\Entity\Collection\AbstractCollectionStub
        )->will(
            $this->returnValue($entityMock)
        );

        $this->model = new AbstractCollectionStub(
            $this->coreEntityFactoryMock,
            $this->loggerMock,
            $this->fetchStrategyMock,
            $this->eventManagerMock,
            $this->configMock,
            $this->coreResourceMock,
            $this->entityFactoryMock,
            $this->resourceHelperMock,
            null,
            null,
            $this->resourceModelPoolMock
        );
    }

    public function tearDown()
    {
        $this->model = null;
    }

    /**
     * Test method \Magento\Eav\Model\Entity\Collection\AbstractCollection::load
     */
    public function testLoad()
    {
        $this->fetchStrategyMock
            ->expects($this->once())
            ->method('fetchAll')
            ->will($this->returnValue([['id' => 1, 'data_changes' => true], ['id' => 2]]));

        foreach ($this->model->getItems() as $item) {
            $this->assertFalse($item->getDataChanges());
        }
    }

    /**
     * @dataProvider getItemsDataProvider
     */
    public function testClear($values, $count)
    {
        $this->fetchStrategyMock->expects($this->once())->method('fetchAll')->will($this->returnValue($values));

        $testId = array_pop($values)['id'];
        $this->assertCount($count, $this->model->getItems());
        $this->assertNotNull($this->model->getItemById($testId));
        $this->model->clear();
        $this->assertNull($this->model->getItemById($testId));
    }

    /**
     * @dataProvider getItemsDataProvider
     */
    public function testRemoveAllItems($values, $count)
    {
        $this->fetchStrategyMock->expects($this->once())->method('fetchAll')->will($this->returnValue($values));

        $testId = array_pop($values)['id'];
        $this->assertCount($count, $this->model->getItems());
        $this->assertNotNull($this->model->getItemById($testId));
        $this->model->removeAllItems();
        $this->assertNull($this->model->getItemById($testId));
    }

    /**
     * @dataProvider getItemsDataProvider
     */
    public function testRemoveItemByKey($values, $count)
    {
        $this->fetchStrategyMock->expects($this->once())->method('fetchAll')->will($this->returnValue($values));

        $testId = array_pop($values)['id'];
        $this->assertCount($count, $this->model->getItems());
        $this->assertNotNull($this->model->getItemById($testId));
        $this->model->removeItemByKey($testId);
        $this->assertCount($count - 1, $this->model->getItems());
        $this->assertNull($this->model->getItemById($testId));
    }

    /**
     * @return array
     */
    public function getItemsDataProvider()
    {
        return [
            ['values' => [['id' => 1]], 'count' => 1],
            ['values' => [['id' => 1], ['id' => 2]], 'count' => 2],
            ['values' => [['id' => 2], ['id' => 3]], 'count' => 2]
        ];
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    public function getMagentoObject()
    {
        return new \Magento\Framework\DataObject();
    }
}

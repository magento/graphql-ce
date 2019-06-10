<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Mview\Test\Unit\View;

/**
 * Test Coverage for Changelog View.
 *
 * @see \Magento\Framework\Mview\View\Changelog
 */
class ChangelogTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Mview\View\Changelog
     */
    protected $model;

    /**
     * Mysql PDO DB adapter mock
     *
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected $connectionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\ResourceConnection
     */
    protected $resourceMock;

    protected function setUp()
    {
        $this->connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);

        $this->resourceMock = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->mockGetConnection($this->connectionMock);

        $this->model = new \Magento\Framework\Mview\View\Changelog($this->resourceMock);
    }

    public function testInstanceOf()
    {
        $resourceMock =
            $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $resourceMock->expects($this->once())->method('getConnection')->will($this->returnValue(true));
        $model = new \Magento\Framework\Mview\View\Changelog($resourceMock);
        $this->assertInstanceOf(\Magento\Framework\Mview\View\ChangelogInterface::class, $model);
    }

    /**
     * @expectedException \Magento\Framework\DB\Adapter\ConnectionException
     * @expectedExceptionMessage The write connection to the database isn't available. Please try again later.
     */
    public function testCheckConnectionException()
    {
        $resourceMock =
            $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $resourceMock->expects($this->once())->method('getConnection')->will($this->returnValue(null));
        $model = new \Magento\Framework\Mview\View\Changelog($resourceMock);
        $model->setViewId('ViewIdTest');
        $this->assertNull($model);
    }

    public function testGetName()
    {
        $this->model->setViewId('ViewIdTest');
        $this->assertEquals(
            'ViewIdTest' . '_' . \Magento\Framework\Mview\View\Changelog::NAME_SUFFIX,
            $this->model->getName()
        );
    }

    public function testGetViewId()
    {
        $this->model->setViewId('ViewIdTest');
        $this->assertEquals('ViewIdTest', $this->model->getViewId());
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage View's identifier is not set
     */
    public function testGetNameWithException()
    {
        $this->model->getName();
    }

    public function testGetColumnName()
    {
        $this->assertEquals(\Magento\Framework\Mview\View\Changelog::COLUMN_NAME, $this->model->getColumnName());
    }

    public function testGetVersion()
    {
        $changelogTableName = 'viewIdtest_cl';
        $this->mockIsTableExists($changelogTableName, true);
        $this->mockGetTableName();

        $this->connectionMock->expects($this->once())
            ->method('fetchRow')
            ->will($this->returnValue(['Auto_increment' => 11]));

        $this->model->setViewId('viewIdtest');
        $this->assertEquals(10, $this->model->getVersion());
    }

    /**
     * @expectedException \Magento\Framework\Exception\RuntimeException
     * @expectedExceptionMessage Table status for viewIdtest_cl is incorrect. Can`t fetch version id.
     */
    public function testGetVersionWithExceptionNoAutoincrement()
    {
        $changelogTableName = 'viewIdtest_cl';
        $this->mockIsTableExists($changelogTableName, true);
        $this->mockGetTableName();

        $this->connectionMock->expects($this->once())
            ->method('fetchRow')
            ->will($this->returnValue([]));

        $this->model->setViewId('viewIdtest');
        $this->model->getVersion();
    }

    public function testGetVersionWithExceptionNoTable()
    {
        $changelogTableName = 'viewIdtest_cl';
        $this->mockIsTableExists($changelogTableName, false);
        $this->mockGetTableName();

        $this->expectException('Exception');
        $this->expectExceptionMessage("Table {$changelogTableName} does not exist");
        $this->model->setViewId('viewIdtest');
        $this->model->getVersion();
    }

    public function testDrop()
    {
        $changelogTableName = 'viewIdtest_cl';
        $this->mockIsTableExists($changelogTableName, false);
        $this->mockGetTableName();

        $this->expectException('Exception');
        $this->expectExceptionMessage("Table {$changelogTableName} does not exist");
        $this->model->setViewId('viewIdtest');
        $this->model->drop();
    }

    public function testDropWithException()
    {
        $changelogTableName = 'viewIdtest_cl';
        $this->mockIsTableExists($changelogTableName, true);
        $this->mockGetTableName();

        $this->connectionMock->expects($this->once())
            ->method('dropTable')
            ->with($changelogTableName)
            ->will($this->returnValue(true));

        $this->model->setViewId('viewIdtest');
        $this->model->drop();
    }

    public function testCreate()
    {
        $changelogTableName = 'viewIdtest_cl';
        $this->mockIsTableExists($changelogTableName, false);
        $this->mockGetTableName();

        $tableMock = $this->createMock(\Magento\Framework\DB\Ddl\Table::class);
        $tableMock->expects($this->exactly(2))
            ->method('addColumn')
            ->will($this->returnSelf());

        $this->connectionMock->expects($this->once())
            ->method('newTable')
            ->with($changelogTableName)
            ->will($this->returnValue($tableMock));
        $this->connectionMock->expects($this->once())
            ->method('createTable')
            ->with($tableMock);

        $this->model->setViewId('viewIdtest');
        $this->model->create();
    }

    public function testCreateWithExistingTable()
    {
        $changelogTableName = 'viewIdtest_cl';
        $this->mockIsTableExists($changelogTableName, true);
        $this->mockGetTableName();

        $this->connectionMock->expects($this->never())->method('createTable');
        $this->model->setViewId('viewIdtest');
        $this->model->create();
    }

    public function testGetList()
    {
        $changelogTableName = 'viewIdtest_cl';
        $this->mockIsTableExists($changelogTableName, true);
        $this->mockGetTableName();

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $selectMock->expects($this->once())
            ->method('distinct')
            ->with(true)
            ->will($this->returnSelf());
        $selectMock->expects($this->once())
            ->method('from')
            ->with($changelogTableName, ['entity_id'])
            ->will($this->returnSelf());
        $selectMock->expects($this->exactly(2))
            ->method('where')
            ->will($this->returnSelf());

        $this->connectionMock->expects($this->once())
            ->method('select')
            ->will($this->returnValue($selectMock));
        $this->connectionMock->expects($this->once())
            ->method('fetchCol')
            ->with($selectMock)
            ->will($this->returnValue([1]));

        $this->model->setViewId('viewIdtest');
        $this->assertEquals([1], $this->model->getList(1, 2));
    }

    public function testGetListWithException()
    {
        $changelogTableName = 'viewIdtest_cl';
        $this->mockIsTableExists($changelogTableName, false);
        $this->mockGetTableName();

        $this->expectException('Exception');
        $this->expectExceptionMessage("Table {$changelogTableName} does not exist");
        $this->model->setViewId('viewIdtest');
        $this->model->getList(mt_rand(1, 200), mt_rand(201, 400));
    }

    public function testClearWithException()
    {
        $changelogTableName = 'viewIdtest_cl';
        $this->mockIsTableExists($changelogTableName, false);
        $this->mockGetTableName();

        $this->expectException('Exception');
        $this->expectExceptionMessage("Table {$changelogTableName} does not exist");
        $this->model->setViewId('viewIdtest');
        $this->model->clear(mt_rand(1, 200));
    }

    /**
     * @param $connection
     */
    protected function mockGetConnection($connection)
    {
        $this->resourceMock->expects($this->once())->method('getConnection')->will($this->returnValue($connection));
    }

    protected function mockGetTableName()
    {
        $this->resourceMock->expects($this->once())->method('getTableName')->will($this->returnArgument(0));
    }

    /**
     * @param $changelogTableName
     * @param $result
     */
    protected function mockIsTableExists($changelogTableName, $result)
    {
        $this->connectionMock->expects(
            $this->once()
        )->method(
            'isTableExists'
        )->with(
            $this->equalTo($changelogTableName)
        )->will(
            $this->returnValue($result)
        );
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\UrlRewrite\Test\Unit\Model\Storage;

use Magento\Framework\DB\Select;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\UrlRewrite\Model\Storage\DbStorage;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class DbStorageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlRewriteFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataObjectHelper;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Framework\DB\Select|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $select;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resource;

    /**
     * @var \Magento\UrlRewrite\Model\Storage\DbStorage
     */
    protected $storage;

    protected function setUp()
    {
        $this->urlRewriteFactory = $this->getMockBuilder(\Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()->getMock();
        $this->dataObjectHelper = $this->createMock(\Magento\Framework\Api\DataObjectHelper::class);
        $this->connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resource = $this->createMock(\Magento\Framework\App\ResourceConnection::class);

        $this->resource->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->connectionMock));
        $this->connectionMock->expects($this->any())
            ->method('select')
            ->will($this->returnValue($this->select));

        $this->storage = (new ObjectManager($this))->getObject(
            \Magento\UrlRewrite\Model\Storage\DbStorage::class,
            [
                'urlRewriteFactory' => $this->urlRewriteFactory,
                'dataObjectHelper' => $this->dataObjectHelper,
                'resource' => $this->resource,
            ]
        );
    }

    public function testFindAllByData()
    {
        $data = ['col1' => 'val1', 'col2' => 'val2'];

        $this->select->expects($this->at(1))
            ->method('where')
            ->with('col1 IN (?)', 'val1');

        $this->select->expects($this->at(2))
            ->method('where')
            ->with('col2 IN (?)', 'val2');

        $this->connectionMock->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnArgument(0));

        $this->connectionMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->select)
            ->will($this->returnValue([['row1'], ['row2']]));

        $this->dataObjectHelper->expects($this->at(0))
            ->method('populateWithArray')
            ->with(['urlRewrite1'], ['row1'], \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::class)
            ->will($this->returnSelf());

        $this->urlRewriteFactory->expects($this->at(0))
            ->method('create')
            ->will($this->returnValue(['urlRewrite1']));

        $this->dataObjectHelper->expects($this->at(1))
            ->method('populateWithArray')
            ->with(['urlRewrite2'], ['row2'], \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::class)
            ->will($this->returnSelf());

        $this->urlRewriteFactory->expects($this->at(1))
            ->method('create')
            ->will($this->returnValue(['urlRewrite2']));

        $this->assertEquals([['urlRewrite1'], ['urlRewrite2']], $this->storage->findAllByData($data));
    }

    public function testFindOneByData()
    {
        $data = ['col1' => 'val1', 'col2' => 'val2'];

        $this->select->expects($this->at(1))
            ->method('where')
            ->with('col1 IN (?)', 'val1');

        $this->select->expects($this->at(2))
            ->method('where')
            ->with('col2 IN (?)', 'val2');

        $this->connectionMock->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnArgument(0));

        $this->connectionMock->expects($this->once())
            ->method('fetchRow')
            ->with($this->select)
            ->will($this->returnValue(['row1']));

        $this->connectionMock->expects($this->never())->method('fetchAll');

        $this->dataObjectHelper->expects($this->at(0))
            ->method('populateWithArray')
            ->with(['urlRewrite1'], ['row1'], \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::class)
            ->will($this->returnSelf());

        $this->urlRewriteFactory->expects($this->at(0))
            ->method('create')
            ->will($this->returnValue(['urlRewrite1']));

        $this->assertEquals(['urlRewrite1'], $this->storage->findOneByData($data));
    }

    public function testFindOneByDataWithRequestPath()
    {
        $origRequestPath = 'page-one';
        $data = [
            'col1'                   => 'val1',
            'col2'                   => 'val2',
            UrlRewrite::REQUEST_PATH => $origRequestPath,
        ];

        $this->select->expects($this->at(1))
            ->method('where')
            ->with('col1 IN (?)', 'val1');

        $this->select->expects($this->at(2))
            ->method('where')
            ->with('col2 IN (?)', 'val2');

        $this->select->expects($this->at(3))
            ->method('where')
            ->with('request_path IN (?)', [$origRequestPath, $origRequestPath . '/']);

        $this->connectionMock->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnArgument(0));

        $this->connectionMock->expects($this->never())
            ->method('fetchRow');

        $urlRewriteRowInDb = [
            UrlRewrite::REQUEST_PATH  => $origRequestPath,
            UrlRewrite::REDIRECT_TYPE => 0,
        ];

        $this->connectionMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->select)
            ->will($this->returnValue([$urlRewriteRowInDb]));

        $this->dataObjectHelper->expects($this->at(0))
            ->method('populateWithArray')
            ->with(['urlRewrite1'], $urlRewriteRowInDb, \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::class)
            ->will($this->returnSelf());

        $this->urlRewriteFactory->expects($this->at(0))
            ->method('create')
            ->will($this->returnValue(['urlRewrite1']));

        $this->assertEquals(['urlRewrite1'], $this->storage->findOneByData($data));
    }

    public function testFindOneByDataWithRequestPathIsDifferent()
    {
        $origRequestPath = 'page-one';
        $data = [
            'col1'                   => 'val1',
            'col2'                   => 'val2',
            UrlRewrite::REQUEST_PATH => $origRequestPath,
        ];

        $this->select->expects($this->at(1))
            ->method('where')
            ->with('col1 IN (?)', 'val1');

        $this->select->expects($this->at(2))
            ->method('where')
            ->with('col2 IN (?)', 'val2');

        $this->select->expects($this->at(3))
            ->method('where')
            ->with('request_path IN (?)', [$origRequestPath, $origRequestPath . '/']);

        $this->connectionMock->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnArgument(0));

        $this->connectionMock->expects($this->never())
            ->method('fetchRow');

        $urlRewriteRowInDb = [
            UrlRewrite::REQUEST_PATH  => $origRequestPath . '/',
            UrlRewrite::REDIRECT_TYPE => 0,
            UrlRewrite::STORE_ID      => 1,
        ];

        $this->connectionMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->select)
            ->will($this->returnValue([$urlRewriteRowInDb]));

        $urlRewriteRedirect = [
            'request_path'     => $origRequestPath,
            'redirect_type'    => 301,
            'store_id'         => 1,
            'entity_type'      => 'custom',
            'entity_id'        => '0',
            'target_path'      => $origRequestPath . '/',
            'description'      => null,
            'is_autogenerated' => '0',
            'metadata'         => null,
        ];

        $this->dataObjectHelper->expects($this->at(0))
            ->method('populateWithArray')
            ->with(['urlRewrite1'], $urlRewriteRedirect, \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::class)
            ->will($this->returnSelf());

        $this->urlRewriteFactory->expects($this->at(0))
            ->method('create')
            ->will($this->returnValue(['urlRewrite1']));

        $this->assertEquals(['urlRewrite1'], $this->storage->findOneByData($data));
    }

    public function testFindOneByDataWithRequestPathIsDifferent2()
    {
        $origRequestPath = 'page-one/';
        $data = [
            'col1'                   => 'val1',
            'col2'                   => 'val2',
            UrlRewrite::REQUEST_PATH => $origRequestPath,
        ];

        $this->select->expects($this->at(1))
            ->method('where')
            ->with('col1 IN (?)', 'val1');

        $this->select->expects($this->at(2))
            ->method('where')
            ->with('col2 IN (?)', 'val2');

        $this->select->expects($this->at(3))
            ->method('where')
            ->with('request_path IN (?)', [rtrim($origRequestPath, '/'), rtrim($origRequestPath, '/') . '/']);

        $this->connectionMock->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnArgument(0));

        $this->connectionMock->expects($this->never())
            ->method('fetchRow');

        $urlRewriteRowInDb = [
            UrlRewrite::REQUEST_PATH  => rtrim($origRequestPath, '/'),
            UrlRewrite::REDIRECT_TYPE => 0,
            UrlRewrite::STORE_ID      => 1,
        ];

        $this->connectionMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->select)
            ->will($this->returnValue([$urlRewriteRowInDb]));

        $urlRewriteRedirect = [
            'request_path'     => $origRequestPath,
            'redirect_type'    => 301,
            'store_id'         => 1,
            'entity_type'      => 'custom',
            'entity_id'        => '0',
            'target_path'      => rtrim($origRequestPath, '/'),
            'description'      => null,
            'is_autogenerated' => '0',
            'metadata'         => null,
        ];

        $this->dataObjectHelper->expects($this->at(0))
            ->method('populateWithArray')
            ->with(['urlRewrite1'], $urlRewriteRedirect, \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::class)
            ->will($this->returnSelf());

        $this->urlRewriteFactory->expects($this->at(0))
            ->method('create')
            ->will($this->returnValue(['urlRewrite1']));

        $this->assertEquals(['urlRewrite1'], $this->storage->findOneByData($data));
    }

    public function testFindOneByDataWithRequestPathIsRedirect()
    {
        $origRequestPath = 'page-one';
        $data = [
            'col1'                   => 'val1',
            'col2'                   => 'val2',
            UrlRewrite::REQUEST_PATH => $origRequestPath,
        ];

        $this->select->expects($this->at(1))
            ->method('where')
            ->with('col1 IN (?)', 'val1');

        $this->select->expects($this->at(2))
            ->method('where')
            ->with('col2 IN (?)', 'val2');

        $this->select->expects($this->at(3))
            ->method('where')
            ->with('request_path IN (?)', [$origRequestPath, $origRequestPath . '/']);

        $this->connectionMock->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnArgument(0));

        $this->connectionMock->expects($this->never())
            ->method('fetchRow');

        $urlRewriteRowInDb = [
            UrlRewrite::REQUEST_PATH  => $origRequestPath . '/',
            UrlRewrite::TARGET_PATH   => 'page-A/',
            UrlRewrite::REDIRECT_TYPE => 301,
            UrlRewrite::STORE_ID      => 1,
        ];

        $this->connectionMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->select)
            ->will($this->returnValue([$urlRewriteRowInDb]));

        $this->dataObjectHelper->expects($this->at(0))
            ->method('populateWithArray')
            ->with(['urlRewrite1'], $urlRewriteRowInDb, \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::class)
            ->will($this->returnSelf());

        $this->urlRewriteFactory->expects($this->at(0))
            ->method('create')
            ->will($this->returnValue(['urlRewrite1']));

        $this->assertEquals(['urlRewrite1'], $this->storage->findOneByData($data));
    }

    public function testFindOneByDataWithRequestPathTwoResults()
    {
        $origRequestPath = 'page-one';
        $data = [
            'col1'                   => 'val1',
            'col2'                   => 'val2',
            UrlRewrite::REQUEST_PATH => $origRequestPath,
        ];

        $this->select->expects($this->at(1))
            ->method('where')
            ->with('col1 IN (?)', 'val1');

        $this->select->expects($this->at(2))
            ->method('where')
            ->with('col2 IN (?)', 'val2');

        $this->select->expects($this->at(3))
            ->method('where')
            ->with('request_path IN (?)', [$origRequestPath, $origRequestPath . '/']);

        $this->connectionMock->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnArgument(0));

        $this->connectionMock->expects($this->never())
            ->method('fetchRow');

        $urlRewriteRowInDb = [
            UrlRewrite::REQUEST_PATH  => $origRequestPath . '/',
            UrlRewrite::TARGET_PATH  => 'page-A/',
            UrlRewrite::REDIRECT_TYPE => 301,
            UrlRewrite::STORE_ID      => 1,
        ];

        $urlRewriteRowInDb2 = [
            UrlRewrite::REQUEST_PATH  => $origRequestPath,
            UrlRewrite::TARGET_PATH  => 'page-B/',
            UrlRewrite::REDIRECT_TYPE => 301,
            UrlRewrite::STORE_ID      => 1,
        ];

        $this->connectionMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->select)
            ->will($this->returnValue([$urlRewriteRowInDb, $urlRewriteRowInDb2]));

        $this->dataObjectHelper->expects($this->at(0))
            ->method('populateWithArray')
            ->with(['urlRewrite1'], $urlRewriteRowInDb2, \Magento\UrlRewrite\Service\V1\Data\UrlRewrite::class)
            ->will($this->returnSelf());

        $this->urlRewriteFactory->expects($this->at(0))
            ->method('create')
            ->will($this->returnValue(['urlRewrite1']));

        $this->assertEquals(['urlRewrite1'], $this->storage->findOneByData($data));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testReplace()
    {
        $urlFirst = $this->createMock(\Magento\UrlRewrite\Service\V1\Data\UrlRewrite::class);
        $urlSecond = $this->createMock(\Magento\UrlRewrite\Service\V1\Data\UrlRewrite::class);

        // delete
        $urlFirst->expects($this->any())
            ->method('getEntityType')
            ->willReturn('product');
        $urlFirst->expects($this->any())
            ->method('getEntityId')
            ->willReturn('entity_1');
        $urlFirst->expects($this->any())
            ->method('getStoreId')
            ->willReturn('store_id_1');

        $urlSecond->expects($this->any())
            ->method('getEntityType')
            ->willReturn('category');
        $urlSecond->expects($this->any())
            ->method('getEntityId')
            ->willReturn('entity_2');
        $urlSecond->expects($this->any())
            ->method('getStoreId')
            ->willReturn('store_id_2');

        $this->connectionMock->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnArgument(0));

        $this->select->expects($this->any())
            ->method($this->anything())
            ->willReturnSelf();

        $this->resource->expects($this->any())
            ->method('getTableName')
            ->with(DbStorage::TABLE_NAME)
            ->will($this->returnValue('table_name'));

        // insert

        $urlFirst->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue(['row1']));
        $urlSecond->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue(['row2']));

        $this->resource->expects($this->any())
            ->method('getTableName')
            ->with(DbStorage::TABLE_NAME)
            ->will($this->returnValue('table_name'));

        $this->storage->replace([$urlFirst, $urlSecond]);
    }

    /**
     * @expectedException \Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException
     */
    public function testReplaceIfThrewExceptionOnDuplicateUrl()
    {
        $url = $this->createMock(\Magento\UrlRewrite\Service\V1\Data\UrlRewrite::class);

        $url->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue(['row1']));

        $this->connectionMock->expects($this->once())
            ->method('insertMultiple')
            ->will(
                $this->throwException(
                    new \Exception('SQLSTATE[23000]: test: 1062 test', DbStorage::ERROR_CODE_DUPLICATE_ENTRY)
                )
            );
        $conflictingUrl = [
            UrlRewrite::URL_REWRITE_ID => 'conflicting-url'
        ];
        $this->connectionMock->expects($this->any())
            ->method('fetchRow')
            ->willReturn($conflictingUrl);

        $this->storage->replace([$url]);
    }

    /**
     * Validates a case when DB errors on duplicate entry, but calculated URLs are not really duplicated
     *
     * An example is when URL length exceeds length of the DB field, so URLs are trimmed and become conflicting
     *
     * @expectedException \Exception
     * @expectedExceptionMessage SQLSTATE[23000]: test: 1062 test
     */
    public function testReplaceIfThrewExceptionOnDuplicateEntry()
    {
        $url = $this->createMock(\Magento\UrlRewrite\Service\V1\Data\UrlRewrite::class);

        $url->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue(['row1']));

        $this->connectionMock->expects($this->once())
            ->method('insertMultiple')
            ->will(
                $this->throwException(
                    new \Exception('SQLSTATE[23000]: test: 1062 test', DbStorage::ERROR_CODE_DUPLICATE_ENTRY)
                )
            );

        $this->storage->replace([$url]);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testReplaceIfThrewCustomException()
    {
        $url = $this->createMock(\Magento\UrlRewrite\Service\V1\Data\UrlRewrite::class);

        $url->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue(['row1']));

        $this->connectionMock->expects($this->once())
            ->method('insertMultiple')
            ->will($this->throwException(new \RuntimeException()));

        $this->storage->replace([$url]);
    }

    public function testDeleteByData()
    {
        $data = ['col1' => 'val1', 'col2' => 'val2'];

        $this->connectionMock->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnArgument(0));

        $this->select->expects($this->at(1))
            ->method('where')
            ->with('col1 IN (?)', 'val1');

        $this->select->expects($this->at(2))
            ->method('where')
            ->with('col2 IN (?)', 'val2');

        $this->select->expects($this->at(3))
            ->method('deleteFromSelect')
            ->with('table_name')
            ->will($this->returnValue('sql delete query'));

        $this->resource->expects($this->any())
            ->method('getTableName')
            ->with(DbStorage::TABLE_NAME)
            ->will($this->returnValue('table_name'));

        $this->connectionMock->expects($this->once())
            ->method('query')
            ->with('sql delete query');

        $this->storage->deleteByData($data);
    }
}

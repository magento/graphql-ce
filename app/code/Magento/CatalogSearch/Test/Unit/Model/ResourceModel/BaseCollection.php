<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogSearch\Test\Unit\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\ResourceModelPoolInterface;

/**
 * Base class for Collection tests.
 *
 * Contains helper methods to get commonly used mocks used for collection tests.
 *
 * @deprecated
 * @see \Magento\ElasticSearch
 **/
class BaseCollection extends \PHPUnit\Framework\TestCase
{
    /**
     * Get Mocks for StoreManager so Collection can be used.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getStoreManager()
    {
        $store = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $store->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($store);

        return $storeManager;
    }

    /**
     * Get mock for ResourceModelPool so Collection can be used.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ResourceModelPoolInterface
     */
    protected function getResourceModelPool()
    {
        $connection = $this->getMockBuilder(\Magento\Framework\DB\Adapter\Pdo\Mysql::class)
            ->disableOriginalConstructor()
            ->setMethods(['select'])
            ->getMockForAbstractClass();
        $select = $this->createMock(\Magento\Framework\DB\Select::class);
        $connection->expects($this->any())->method('select')->willReturn($select);

        $entity = $this->getMockBuilder(\Magento\Eav\Model\Entity\AbstractEntity::class)
            ->setMethods(['getConnection', 'getTable', 'getDefaultAttributes', 'getEntityTable'])
            ->disableOriginalConstructor()
            ->getMock();
        $entity->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);
        $entity->expects($this->exactly(2))
            ->method('getTable')
            ->willReturnArgument(0);
        $entity->expects($this->once())
            ->method('getDefaultAttributes')
            ->willReturn(['attr1', 'attr2']);
        $entity->expects($this->once())
            ->method('getEntityTable')
            ->willReturn('table');

        $resourceModelPool = $this->getMockBuilder(ResourceModelPoolInterface::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
        $resourceModelPool->expects($this->once())
            ->method('get')
            ->willReturn($entity);

        return $resourceModelPool;
    }
}

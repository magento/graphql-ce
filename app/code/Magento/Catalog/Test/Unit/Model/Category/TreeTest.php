<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Catalog\Test\Unit\Model\Category;

class TreeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Magento\Catalog\Model\ResourceModel\Category\Tree
     */
    protected $categoryTreeMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    protected $categoryCollection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Magento\Catalog\Api\Data\CategoryTreeInterfaceFactory
     */
    protected $treeFactoryMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Catalog\Model\Category\Tree
     */
    protected $tree;

    /**
     * @var \Magento\Catalog\Model\Category\Tree
     */
    protected $node;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\TreeFactory
     */
    private $treeResourceFactoryMock;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->categoryTreeMock = $this->getMockBuilder(
            \Magento\Catalog\Model\ResourceModel\Category\Tree::class
        )->disableOriginalConstructor()->getMock();

        $this->categoryCollection = $this->getMockBuilder(
            \Magento\Catalog\Model\ResourceModel\Category\Collection::class
        )->disableOriginalConstructor()->getMock();

        $this->storeManagerMock = $this->getMockBuilder(
            \Magento\Store\Model\StoreManagerInterface::class
        )->disableOriginalConstructor()->getMock();

        $this->treeResourceFactoryMock = $this->createMock(
            \Magento\Catalog\Model\ResourceModel\Category\TreeFactory::class
        );
        $this->treeResourceFactoryMock->method('create')
            ->willReturn($this->categoryTreeMock);

        $methods = ['create'];
        $this->treeFactoryMock =
            $this->createPartialMock(\Magento\Catalog\Api\Data\CategoryTreeInterfaceFactory::class, $methods);

        $this->tree = $this->objectManager
            ->getObject(
                \Magento\Catalog\Model\Category\Tree::class,
                [
                    'categoryCollection' => $this->categoryCollection,
                    'categoryTree' => $this->categoryTreeMock,
                    'storeManager' => $this->storeManagerMock,
                    'treeFactory' => $this->treeFactoryMock,
                    'treeResourceFactory' => $this->treeResourceFactoryMock,
                ]
            );
    }

    public function testGetNode()
    {
        $category = $this->getMockBuilder(
            \Magento\Catalog\Model\Category::class
        )->disableOriginalConstructor()->getMock();
        $category->expects($this->exactly(2))->method('getId')->will($this->returnValue(1));

        $node = $this->getMockBuilder(
            \Magento\Framework\Data\Tree\Node::class
        )->disableOriginalConstructor()->getMock();

        $node->expects($this->once())->method('loadChildren');
        $this->categoryTreeMock->expects($this->once())->method('loadNode')
            ->with($this->equalTo(1))
            ->will($this->returnValue($node));

        $store = $this->getMockBuilder(\Magento\Store\Model\Store::class)->disableOriginalConstructor()->getMock();
        $store->expects($this->once())->method('getId')->will($this->returnValue(1));
        $this->storeManagerMock->expects($this->once())->method('getStore')->will($this->returnValue($store));

        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')->will($this->returnSelf());
        $this->categoryCollection->expects($this->once())->method('setProductStoreId')->will($this->returnSelf());
        $this->categoryCollection->expects($this->once())->method('setLoadProductCount')->will($this->returnSelf());
        $this->categoryCollection->expects($this->once())->method('setStoreId')->will($this->returnSelf());

        $this->categoryTreeMock->expects($this->once())->method('addCollectionData')
            ->with($this->equalTo($this->categoryCollection));
        $this->tree->getRootNode($category);
    }

    public function testGetRootNode()
    {
        $store = $this->getMockBuilder(\Magento\Store\Model\Store::class)->disableOriginalConstructor()->getMock();
        $store->expects($this->once())->method('getRootCategoryId')->will($this->returnValue(2));
        $store->expects($this->once())->method('getId')->will($this->returnValue(1));
        $this->storeManagerMock->expects($this->any())->method('getStore')->will($this->returnValue($store));

        $this->categoryCollection->expects($this->any())->method('addAttributeToSelect')->will($this->returnSelf());
        $this->categoryCollection->expects($this->once())->method('setProductStoreId')->will($this->returnSelf());
        $this->categoryCollection->expects($this->once())->method('setLoadProductCount')->will($this->returnSelf());
        $this->categoryCollection->expects($this->once())->method('setStoreId')->will($this->returnSelf());

        $node = $this->getMockBuilder(
            \Magento\Catalog\Model\ResourceModel\Category\Tree::class
        )->disableOriginalConstructor()
        ->getMock();
        $node->expects($this->once())->method('addCollectionData')
            ->with($this->equalTo($this->categoryCollection));
        $node->expects($this->once())->method('getNodeById')->with($this->equalTo(2));
        $this->categoryTreeMock->expects($this->once())->method('load')
            ->with($this->equalTo(null))
            ->will($this->returnValue($node));
        $this->tree->getRootNode();
    }

    public function testGetTree()
    {
        $depth = 2;
        $currentLevel = 1;

        $treeNodeMock1 = $this->createMock(\Magento\Catalog\Api\Data\CategoryTreeInterface::class);
        $treeNodeMock1->expects($this->once())->method('setId')->with($this->equalTo($currentLevel))
            ->will($this->returnSelf());
        $treeNodeMock1->expects($this->once())->method('setParentId')->with($this->equalTo($currentLevel - 1))
            ->will($this->returnSelf());
        $treeNodeMock1->expects($this->once())->method('setName')->with($this->equalTo('Name' . $currentLevel))
            ->will($this->returnSelf());
        $treeNodeMock1->expects($this->once())->method('setPosition')->with($this->equalTo($currentLevel))
            ->will($this->returnSelf());
        $treeNodeMock1->expects($this->once())->method('setLevel')->with($this->equalTo($currentLevel))
            ->will($this->returnSelf());
        $treeNodeMock1->expects($this->once())->method('setIsActive')->with($this->equalTo(true))
            ->will($this->returnSelf());
        $treeNodeMock1->expects($this->once())->method('setProductCount')->with(4)
            ->will($this->returnSelf());
        $treeNodeMock1->expects($this->once())->method('setChildrenData')->will($this->returnSelf());

        $treeNodeMock2 = $this->createMock(\Magento\Catalog\Api\Data\CategoryTreeInterface::class);
        $treeNodeMock2->expects($this->once())->method('setId')->with($this->equalTo($currentLevel))
            ->will($this->returnSelf());
        $treeNodeMock2->expects($this->once())->method('setParentId')->with($this->equalTo($currentLevel - 1))
            ->will($this->returnSelf());
        $treeNodeMock2->expects($this->once())->method('setName')->with($this->equalTo('Name' . $currentLevel))
            ->will($this->returnSelf());
        $treeNodeMock2->expects($this->once())->method('setPosition')->with($this->equalTo($currentLevel))
            ->will($this->returnSelf());
        $treeNodeMock2->expects($this->once())->method('setLevel')->with($this->equalTo($currentLevel))
            ->will($this->returnSelf());
        $treeNodeMock2->expects($this->once())->method('setIsActive')->with($this->equalTo(true))
            ->will($this->returnSelf());
        $treeNodeMock2->expects($this->once())->method('setProductCount')->with(4)
            ->will($this->returnSelf());
        $treeNodeMock2->expects($this->once())->method('setChildrenData')->will($this->returnSelf());

        $this->treeFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->will($this->onConsecutiveCalls($treeNodeMock1, $treeNodeMock2));
        $node = $this->getMockBuilder(\Magento\Framework\Data\Tree\Node::class)->disableOriginalConstructor()
            ->setMethods(
                [
                    'hasChildren',
                    'getChildren',
                    'getId',
                    'getParentId',
                    'getName',
                    'getPosition',
                    'getLevel',
                    'getIsActive',
                    'getProductCount',
                ]
            )
            ->getMock();
        $node->expects($this->any())->method('hasChildren')->will($this->returnValue(true));
        $node->expects($this->any())->method('getChildren')->will($this->returnValue([$node]));

        $node->expects($this->any())->method('getId')->will($this->returnValue($currentLevel));
        $node->expects($this->any())->method('getParentId')->will($this->returnValue($currentLevel - 1));
        $node->expects($this->any())->method('getName')->will($this->returnValue('Name' . $currentLevel));
        $node->expects($this->any())->method('getPosition')->will($this->returnValue($currentLevel));
        $node->expects($this->any())->method('getLevel')->will($this->returnValue($currentLevel));
        $node->expects($this->any())->method('getIsActive')->will($this->returnValue(true));
        $node->expects($this->any())->method('getProductCount')->will($this->returnValue(4));
        $this->tree->getTree($node, $depth, $currentLevel);
    }

    public function testGetTreeWhenChildrenAreNotExist()
    {
        $currentLevel = 1;
        $treeNodeMock = $this->createMock(\Magento\Catalog\Api\Data\CategoryTreeInterface::class);
        $this->treeFactoryMock->expects($this->any())->method('create')->will($this->returnValue($treeNodeMock));
        $treeNodeMock->expects($this->once())->method('setId')->with($this->equalTo($currentLevel))
            ->will($this->returnSelf());
        $treeNodeMock->expects($this->once())->method('setParentId')->with($this->equalTo($currentLevel - 1))
            ->will($this->returnSelf());
        $treeNodeMock->expects($this->once())->method('setName')->with($this->equalTo('Name' . $currentLevel))
            ->will($this->returnSelf());
        $treeNodeMock->expects($this->once())->method('setPosition')->with($this->equalTo($currentLevel))
            ->will($this->returnSelf());
        $treeNodeMock->expects($this->once())->method('setLevel')->with($this->equalTo($currentLevel))
            ->will($this->returnSelf());
        $treeNodeMock->expects($this->once())->method('setIsActive')->with($this->equalTo(true))
            ->will($this->returnSelf());
        $treeNodeMock->expects($this->once())->method('setProductCount')->with(4)
            ->will($this->returnSelf());
        $treeNodeMock->expects($this->once())->method('setChildrenData')->will($this->returnSelf());

        $node = $this->getMockBuilder(\Magento\Framework\Data\Tree\Node::class)->disableOriginalConstructor()
            ->setMethods(
                [
                    'hasChildren',
                    'getChildren',
                    'getId',
                    'getParentId',
                    'getName',
                    'getPosition',
                    'getLevel',
                    'getIsActive',
                    'getProductCount',
                ]
            )
            ->getMock();
        $node->expects($this->any())->method('hasChildren')->will($this->returnValue(false));
        $node->expects($this->never())->method('getChildren');

        $node->expects($this->once())->method('getId')->will($this->returnValue($currentLevel));
        $node->expects($this->once())->method('getParentId')->will($this->returnValue($currentLevel - 1));
        $node->expects($this->once())->method('getName')->will($this->returnValue('Name' . $currentLevel));
        $node->expects($this->once())->method('getPosition')->will($this->returnValue($currentLevel));
        $node->expects($this->once())->method('getLevel')->will($this->returnValue($currentLevel));
        $node->expects($this->once())->method('getIsActive')->will($this->returnValue(true));
        $node->expects($this->once())->method('getProductCount')->will($this->returnValue(4));
        $this->tree->getTree($node);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Catalog\Test\Unit\Model\Category;

use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ExtractDataFromCategoryTree;


class ExtractDataFromCategoryTreeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $category;

    /**
     * @var \Magento\CatalogGraphQl\Model\Category\Hydrator
     */
    protected $categoryHydrator;

    /**
     * @var ExtractDataFromCategoryTree
     */
    protected $dataProvider;


    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->category = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryHydrator = $this->getMockBuilder(\Magento\CatalogGraphQl\Model\Category\Hydrator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataProvider = $objectManager->getObject(
            \Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\ExtractDataFromCategoryTree::class,
            [
                'categoryHydrator' => $this->categoryHydrator
            ]
        );
    }

    public function testExtractData()
    {
        /**
         * Category Structure
         *  2
         *  |--3
         *  |  |--5
         *  |
         *  |--4
         *     |--6
         */


        $cat1 = $this->createMock(\Magento\Catalog\Model\Category::class);
        $cat1->method('getId')->willReturn('2');
        $cat1->method('getLevel')->willReturn('1');
        $cat1->method('getParentId')->willReturn('1');

        $cat2 = $this->createMock(\Magento\Catalog\Model\Category::class);
        $cat2->method('getId')->willReturn('3');
        $cat2->method('getLevel')->willReturn('2');
        $cat2->method('getParentId')->willReturn('2');

        $cat3 = $this->createMock(\Magento\Catalog\Model\Category::class);
        $cat3->method('getId')->willReturn('4');
        $cat3->method('getLevel')->willReturn('2');
        $cat3->method('getParentId')->willReturn('2');

        $cat4 = $this->createMock(\Magento\Catalog\Model\Category::class);
        $cat4->method('getId')->willReturn('5');
        $cat4->method('getLevel')->willReturn('3');
        $cat4->method('getParentId')->willReturn('3');

        $cat5 = $this->createMock(\Magento\Catalog\Model\Category::class);
        $cat5->method('getId')->willReturn('6');
        $cat5->method('getLevel')->willReturn('3');
        $cat5->method('getParentId')->willReturn('4');

        $this->categoryHydrator->method('hydrateCategory')->will($this->returnCallback(
            function ($category) {
                return ['id'=>$category->getId(),'children'=>[]];
            }
        ));


        $result = $this->dataProvider->execute(new \ArrayIterator([$cat1, $cat2, $cat3, $cat4, $cat5]));

        $this->assertEquals(2, count($result['2']['children']), "Root category has two children");
        $this->assertTrue(isset($result['2']['children'][0]['children']), "First sub-cat has children");
        $this->assertTrue(isset($result['2']['children'][1]['children']), "Second sub-cat has children");
        $this->assertEquals(1, count($result['2']['children'][0]['children']), "First sub-cat has one child");
        $this->assertEquals(1, count($result['2']['children'][1]['children']), "Second sub-cat has one child");
    }
}

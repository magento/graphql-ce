<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Downloadable\Test\Unit\Block\Adminhtml\Catalog\Product\Edit\Tab\Downloadable;

/**
 * Class LinksTest
 *
 * @package Magento\Downloadable\Test\Unit\Block\Adminhtml\Catalog\Product\Edit\Tab\Downloadable
 *
 * @deprecated
 * @see \Magento\Downloadable\Ui\DataProvider\Product\Form\Modifier\Links
 */
class LinksTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Downloadable\Block\Adminhtml\Catalog\Product\Edit\Tab\Downloadable\Links
     */
    protected $block;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $productModel;

    /**
     * @var \Magento\Downloadable\Model\Product\Type
     */
    protected $downloadableProductModel;

    /**
     * @var \Magento\Downloadable\Model\Link
     */
    protected $downloadableLinkModel;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Magento\Downloadable\Helper\File
     */
    protected $fileHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Backend\Model\Url
     */
    protected $urlBuilder;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->urlBuilder = $this->createPartialMock(\Magento\Backend\Model\Url::class, ['getUrl']);
        $attributeFactory = $this->createMock(\Magento\Eav\Model\Entity\AttributeFactory::class);
        $urlFactory = $this->createMock(\Magento\Backend\Model\UrlFactory::class);
        $this->fileHelper = $this->createPartialMock(\Magento\Downloadable\Helper\File::class, [
                'getFilePath',
                'ensureFileInFilesystem',
                'getFileSize'
            ]);
        $this->productModel = $this->createPartialMock(\Magento\Catalog\Model\Product::class, [
                '__wakeup',
                'getTypeId',
                'getTypeInstance',
                'getStoreId'
            ]);
        $this->downloadableProductModel = $this->createPartialMock(\Magento\Downloadable\Model\Product\Type::class, [
                '__wakeup',
                'getLinks'
            ]);
        $this->downloadableLinkModel = $this->createPartialMock(\Magento\Downloadable\Model\Link::class, [
                '__wakeup',
                'getId',
                'getTitle',
                'getPrice',
                'getNumberOfDownloads',
                'getLinkUrl',
                'getLinkType',
                'getSampleFile',
                'getSampleType',
                'getSortOrder',
                'getLinkFile',
                'getStoreTitle'
            ]);

        $this->coreRegistry = $this->createPartialMock(\Magento\Framework\Registry::class, [
                '__wakeup',
                'registry'
            ]);

        $this->escaper = $this->createPartialMock(\Magento\Framework\Escaper::class, ['escapeHtml']);

        $this->block = $objectManagerHelper->getObject(
            \Magento\Downloadable\Block\Adminhtml\Catalog\Product\Edit\Tab\Downloadable\Links::class,
            [
                'urlBuilder' => $this->urlBuilder,
                'attributeFactory' => $attributeFactory,
                'urlFactory' => $urlFactory,
                'coreRegistry' => $this->coreRegistry,
                'escaper' => $this->escaper,
                'downloadableFile' => $this->fileHelper
            ]
        );
    }

    /**
     * Test that getConfig method retrieve \Magento\Framework\DataObject object
     */
    public function testGetConfig()
    {
        $this->assertInstanceOf(\Magento\Framework\DataObject::class, $this->block->getConfig());
    }

    public function testGetLinkData()
    {
        $expectingFileData = [
            'file' => [
                'file' => 'file/link.gif',
                'name' => '<a href="final_url">link.gif</a>',
                'size' => '1.1',
                'status' => 'old',
            ],
            'sample_file' => [
                'file' => 'file/sample.gif',
                'name' => '<a href="final_url">sample.gif</a>',
                'size' => '1.1',
                'status' => 'old',
            ],
        ];

        $this->productModel->expects($this->any())->method('getTypeId')
            ->will($this->returnValue('downloadable'));
        $this->productModel->expects($this->any())->method('getTypeInstance')
            ->will($this->returnValue($this->downloadableProductModel));
        $this->productModel->expects($this->any())->method('getStoreId')
            ->will($this->returnValue(0));
        $this->downloadableProductModel->expects($this->any())->method('getLinks')
            ->will($this->returnValue([$this->downloadableLinkModel]));
        $this->coreRegistry->expects($this->any())->method('registry')
            ->will($this->returnValue($this->productModel));
        $this->downloadableLinkModel->expects($this->any())->method('getId')
            ->will($this->returnValue(1));
        $this->downloadableLinkModel->expects($this->any())->method('getTitle')
            ->will($this->returnValue('Link Title'));
        $this->downloadableLinkModel->expects($this->any())->method('getPrice')
            ->will($this->returnValue('10'));
        $this->downloadableLinkModel->expects($this->any())->method('getNumberOfDownloads')
            ->will($this->returnValue('6'));
        $this->downloadableLinkModel->expects($this->any())->method('getLinkUrl')
            ->will($this->returnValue(null));
        $this->downloadableLinkModel->expects($this->any())->method('getLinkType')
            ->will($this->returnValue('file'));
        $this->downloadableLinkModel->expects($this->any())->method('getSampleFile')
            ->will($this->returnValue('file/sample.gif'));
        $this->downloadableLinkModel->expects($this->any())->method('getSampleType')
            ->will($this->returnValue('file'));
        $this->downloadableLinkModel->expects($this->any())->method('getSortOrder')
            ->will($this->returnValue(0));
        $this->downloadableLinkModel->expects($this->any())->method('getLinkFile')
            ->will($this->returnValue('file/link.gif'));
        $this->downloadableLinkModel->expects($this->any())->method('getStoreTitle')
            ->will($this->returnValue('Store Title'));
        $this->escaper->expects($this->any())->method('escapeHtml')
            ->will($this->returnValue('Link Title'));
        $this->fileHelper->expects($this->any())->method('getFilePath')
            ->will($this->returnValue('/file/path/link.gif'));
        $this->fileHelper->expects($this->any())->method('ensureFileInFilesystem')
            ->will($this->returnValue(true));
        $this->fileHelper->expects($this->any())->method('getFileSize')
            ->will($this->returnValue('1.1'));
        $this->urlBuilder->expects($this->any())->method('getUrl')
            ->will($this->returnValue('final_url'));
        $linkData = $this->block->getLinkData();
        foreach ($linkData as $link) {
            $fileSave = $link->getFileSave(0);
            $sampleFileSave = $link->getSampleFileSave(0);
            $this->assertEquals($expectingFileData['file'], $fileSave);
            $this->assertEquals($expectingFileData['sample_file'], $sampleFileSave);
        }
    }
}

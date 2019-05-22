<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Downloadable\Test\Unit\Block\Adminhtml\Catalog\Product\Edit\Tab\Downloadable;

/**
 * Class SamplesTest
 *
 * @package Magento\Downloadable\Test\Unit\Block\Adminhtml\Catalog\Product\Edit\Tab\Downloadable
 *
 * @deprecated
 * @see \Magento\Downloadable\Ui\DataProvider\Product\Form\Modifier\Samples
 */
class SamplesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Downloadable\Block\Adminhtml\Catalog\Product\Edit\Tab\Downloadable\Samples
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
     * @var \Magento\Downloadable\Model\Sample
     */
    protected $downloadableSampleModel;

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
                'getSamples'
            ]);
        $this->downloadableSampleModel = $this->createPartialMock(\Magento\Downloadable\Model\Sample::class, [
                '__wakeup',
                'getId',
                'getTitle',
                'getSampleFile',
                'getSampleType',
                'getSortOrder',
                'getSampleUrl'
            ]);
        $this->coreRegistry = $this->createPartialMock(\Magento\Framework\Registry::class, [
                '__wakeup',
                'registry'
            ]);
        $this->escaper = $this->createPartialMock(\Magento\Framework\Escaper::class, ['escapeHtml']);
        $this->block = $objectManagerHelper->getObject(
            \Magento\Downloadable\Block\Adminhtml\Catalog\Product\Edit\Tab\Downloadable\Samples::class,
            [
                'urlBuilder' => $this->urlBuilder,
                'urlFactory' => $urlFactory,
                'coreRegistry' => $this->coreRegistry,
                'escaper' => $this->escaper,
                'downloadableFile' => $this->fileHelper]
        );
    }

    /**
     * Test that getConfig method retrieve \Magento\Framework\DataObject object
     */
    public function testGetConfig()
    {
        $this->assertInstanceOf(\Magento\Framework\DataObject::class, $this->block->getConfig());
    }

    public function testGetSampleData()
    {
        $expectingFileData = [
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
        $this->downloadableProductModel->expects($this->any())->method('getSamples')
            ->will($this->returnValue([$this->downloadableSampleModel]));
        $this->coreRegistry->expects($this->any())->method('registry')
            ->will($this->returnValue($this->productModel));
        $this->downloadableSampleModel->expects($this->any())->method('getId')
            ->will($this->returnValue(1));
        $this->downloadableSampleModel->expects($this->any())->method('getTitle')
            ->will($this->returnValue('Sample Title'));
        $this->downloadableSampleModel->expects($this->any())->method('getSampleUrl')
            ->will($this->returnValue(null));
        $this->downloadableSampleModel->expects($this->any())->method('getSampleFile')
            ->will($this->returnValue('file/sample.gif'));
        $this->downloadableSampleModel->expects($this->any())->method('getSampleType')
            ->will($this->returnValue('file'));
        $this->downloadableSampleModel->expects($this->any())->method('getSortOrder')
            ->will($this->returnValue(0));
        $this->escaper->expects($this->any())->method('escapeHtml')
            ->will($this->returnValue('Sample Title'));
        $this->fileHelper->expects($this->any())->method('getFilePath')
            ->will($this->returnValue('/file/path/sample.gif'));
        $this->fileHelper->expects($this->any())->method('ensureFileInFilesystem')
            ->will($this->returnValue(true));
        $this->fileHelper->expects($this->any())->method('getFileSize')
            ->will($this->returnValue('1.1'));
        $this->urlBuilder->expects($this->any())->method('getUrl')
            ->will($this->returnValue('final_url'));
        $sampleData = $this->block->getSampleData();
        foreach ($sampleData as $sample) {
            $fileSave = $sample->getFileSave(0);
            $this->assertEquals($expectingFileData['sample_file'], $fileSave);
        }
    }
}

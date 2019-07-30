<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Config\Test\Unit\Reader;

use Magento\Framework\Config\Reader\Filesystem;

/**
 * Test for
 *
 * @see Filesystem
 */
class FilesystemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_fileResolverMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_converterMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_schemaLocatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_validationStateMock;

    /**
     * @var \Magento\Framework\Config\Dom\UrnResolver
     */
    protected $urnResolver;

    /**
     * @var string
     */
    protected $_file;

    protected function setUp()
    {
        if (!function_exists('libxml_set_external_entity_loader')) {
            $this->markTestSkipped('Skipped on HHVM. Will be fixed in MAGETWO-45033');
        }
        $this->_file = file_get_contents(__DIR__ . '/../_files/reader/config.xml');
        $this->_fileResolverMock = $this->createMock(\Magento\Framework\Config\FileResolverInterface::class);
        $this->_converterMock = $this->createMock(\Magento\Framework\Config\ConverterInterface::class);
        $this->_schemaLocatorMock = $this->createMock(\Magento\Framework\Config\SchemaLocatorInterface::class);
        $this->_validationStateMock = $this->createMock(\Magento\Framework\Config\ValidationStateInterface::class);
        $this->urnResolver = new \Magento\Framework\Config\Dom\UrnResolver();
    }

    public function testRead()
    {
        $model = new Filesystem(
            $this->_fileResolverMock,
            $this->_converterMock,
            $this->_schemaLocatorMock,
            $this->_validationStateMock,
            'fileName',
            []
        );
        $this->_fileResolverMock->expects($this->once())->method('get')->will($this->returnValue([$this->_file]));

        $dom = new \DOMDocument();
        $dom->loadXML($this->_file);
        $this->_converterMock->expects($this->once())->method('convert')->with($dom);
        $model->read('scope');
    }

    public function testReadWithoutFiles()
    {
        $model = new Filesystem(
            $this->_fileResolverMock,
            $this->_converterMock,
            $this->_schemaLocatorMock,
            $this->_validationStateMock,
            'fileName',
            []
        );
        $this->_fileResolverMock
            ->expects($this->once())->method('get')->will($this->returnValue([]));

        $this->assertEmpty($model->read('scope'));
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Invalid Document
     */
    public function testReadWithInvalidDom()
    {
        $this->_schemaLocatorMock->expects(
            $this->once()
        )->method(
            'getSchema'
        )->will(
            $this->returnValue(
                $this->urnResolver->getRealPath('urn:magento:framework:Config/Test/Unit/_files/reader/schema.xsd')
            )
        );
        $this->_validationStateMock->expects($this->any())
            ->method('isValidationRequired')
            ->willReturn(true);
        $model = new Filesystem(
            $this->_fileResolverMock,
            $this->_converterMock,
            $this->_schemaLocatorMock,
            $this->_validationStateMock,
            'fileName',
            []
        );
        $this->_fileResolverMock->expects($this->once())->method('get')->will($this->returnValue([$this->_file]));

        $model->read('scope');
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The XML in file "0" is invalid:
     */
    public function testReadWithInvalidXml()
    {
        $this->_schemaLocatorMock->expects(
            $this->any()
        )->method(
            'getPerFileSchema'
        )->will(
            $this->returnValue(
                $this->urnResolver->getRealPath('urn:magento:framework:Config/Test/Unit/_files/reader/schema.xsd')
            )
        );
        $this->_validationStateMock->expects($this->any())
            ->method('isValidationRequired')
            ->willReturn(true);

        $model = new Filesystem(
            $this->_fileResolverMock,
            $this->_converterMock,
            $this->_schemaLocatorMock,
            $this->_validationStateMock,
            'fileName',
            []
        );
        $this->_fileResolverMock->expects($this->once())->method('get')->will($this->returnValue([$this->_file]));
        $model->read('scope');
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Instance of the DOM config merger is expected, got StdClass instead.
     */
    public function testReadException()
    {
        $this->_fileResolverMock->expects($this->once())->method('get')->will($this->returnValue([$this->_file]));
        $model = new Filesystem(
            $this->_fileResolverMock,
            $this->_converterMock,
            $this->_schemaLocatorMock,
            $this->_validationStateMock,
            'fileName',
            [],
            'StdClass'
        );
        $model->read();
    }
}

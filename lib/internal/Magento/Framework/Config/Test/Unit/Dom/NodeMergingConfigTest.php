<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Config\Test\Unit\Dom;

use Magento\Framework\Config\Dom\NodeMergingConfig;
use Magento\Framework\Config\Dom\NodePathMatcher;

/**
 * Test for
 *
 * @see NodeMergingConfig
 */
class NodeMergingConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NodeMergingConfig
     */
    protected $object;

    /**
     * @var NodePathMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $nodePathMatcher;

    protected function setUp()
    {
        $this->nodePathMatcher = $this->createMock(NodePathMatcher::class);
        $this->object = new NodeMergingConfig(
            $this->nodePathMatcher,
            ['/root/one' => 'name', '/root/two' => 'id', '/root/three' => 'key']
        );
    }

    public function testGetIdAttributeMatched()
    {
        $xpath = '/root/two[@attr="value"]';
        $this->nodePathMatcher->expects(
            $this->at(0)
        )->method(
            'match'
        )->with(
            '/root/one',
            $xpath
        )->will(
            $this->returnValue(false)
        );
        $this->nodePathMatcher->expects(
            $this->at(1)
        )->method(
            'match'
        )->with(
            '/root/two',
            $xpath
        )->will(
            $this->returnValue(true)
        );
        $this->assertEquals('id', $this->object->getIdAttribute($xpath));
    }

    public function testGetIdAttributeNotMatched()
    {
        $xpath = '/root/four[@attr="value"]';
        $this->nodePathMatcher->expects(
            $this->at(0)
        )->method(
            'match'
        )->with(
            '/root/one',
            $xpath
        )->will(
            $this->returnValue(false)
        );
        $this->nodePathMatcher->expects(
            $this->at(1)
        )->method(
            'match'
        )->with(
            '/root/two',
            $xpath
        )->will(
            $this->returnValue(false)
        );
        $this->nodePathMatcher->expects(
            $this->at(2)
        )->method(
            'match'
        )->with(
            '/root/three',
            $xpath
        )->will(
            $this->returnValue(false)
        );
        $this->assertNull($this->object->getIdAttribute($xpath));
    }
}

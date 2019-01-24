<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Filter\Test\Unit;

class StripTagsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers \Magento\Framework\Filter\StripTags::filter
     */
    public function testStripTags()
    {
        $escaper = $this->createMock(\Magento\Framework\Escaper::class);
        $stripTags = new \Magento\Framework\Filter\StripTags($escaper);
        $this->assertEquals('three', $stripTags->filter('<two>three</two>'));
    }
}

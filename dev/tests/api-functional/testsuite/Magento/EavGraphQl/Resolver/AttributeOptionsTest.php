<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\EavGraphQl\Resolver;

use Magento\EavGraphQl\Model\Resolver\AttributeOptions;
use Magento\Framework\Exception\LocalizedException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test attribute options class.
 * Test methods throw exceptions.
 */
class AttributeOptionsTest extends GraphQlAbstract
{
    /** @var AttributeOptions */
    private $attributeOptionsResolver;

    protected function setUp(): void
    {
        $this->attributeOptionsResolver = Bootstrap::getObjectManager()->get(AttributeOptions::class);
    }

    /**
     * @expectedException LocalizedException
     * @throws \ReflectionException
     */
    public function testGetEntityTypeThrowsExceptionIfEntityTypeIsNotSet(): void
    {
        $args = [];
        $this->expectException(LocalizedException::class);

        $reflection = new \ReflectionClass($this->attributeOptionsResolver);
        $reflectionMethod = $reflection->getMethod('getEntityType');
        $reflectionMethod->setAccessible(true);

        $reflectionMethod->invoke($this->attributeOptionsResolver, $args);
    }

    /**
     * @expectedException LocalizedException
     * @throws \ReflectionException
     */
    public function testGetAttributeCodeThrowsExceptionIfAttributeCodeIsNotSet(): void
    {
        $args = [];
        $this->expectException(LocalizedException::class);

        $reflection = new \ReflectionClass($this->attributeOptionsResolver);
        $reflectionMethod = $reflection->getMethod('getAttributeCode');
        $reflectionMethod->setAccessible(true);

        $reflectionMethod->invoke($this->attributeOptionsResolver, $args);
    }
}

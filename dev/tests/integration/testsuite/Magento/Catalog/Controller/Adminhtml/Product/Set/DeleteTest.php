<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Controller\Adminhtml\Product\Set;

use Magento\Framework\Message\MessageInterface;
use Magento\Framework\App\Request\Http as HttpRequest;

class DeleteTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @magentoDataFixture Magento/Eav/_files/empty_attribute_set.php
     */
    public function testDeleteById()
    {
        $attributeSet = $this->getAttributeSetByName('empty_attribute_set');
        $this->getRequest()->setParam('id', $attributeSet->getId())->setMethod(HttpRequest::METHOD_POST);

        $this->dispatch('backend/catalog/product_set/delete/');

        $this->assertNull($this->getAttributeSetByName('empty_attribute_set'));
        $this->assertSessionMessages(
            $this->equalTo(['The attribute set has been removed.']),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertRedirect($this->stringContains('catalog/product_set/index/'));
    }

    /**
     * Retrieve attribute set based on given name.
     *
     * @param string $attributeSetName
     * @return \Magento\Eav\Model\Entity\Attribute\Set|null
     */
    protected function getAttributeSetByName($attributeSetName)
    {
        $attributeSet = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Eav\Model\Entity\Attribute\Set::class
        )->load($attributeSetName, 'attribute_set_name');
        return $attributeSet->getId() === null ? null : $attributeSet;
    }
}

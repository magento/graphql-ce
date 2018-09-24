<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ConfigurableProduct\Controller\Adminhtml;

use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\TestFramework\ObjectManager;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Request\Http as HttpRequest;

/**
 * @magentoAppArea adminhtml
 */
class ProductTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/associated_products.php
     */
    public function testSaveActionAssociatedProductIds()
    {
        $associatedProductIds = ['3', '14', '15', '92'];
        $associatedProductIdsJSON = json_encode($associatedProductIds);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(
            [
                'id' => 1,
                'attributes' => [$this->_getConfigurableAttribute()->getId()],
                'associated_product_ids_serialized' => $associatedProductIdsJSON,
            ]
        );

        $this->dispatch('backend/catalog/product/save');

        /** @var $objectManager ObjectManager */
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        /** @var $product Product */
        $product = $objectManager->get(Registry::class)->registry('current_product');
        $configurableProductLinks = array_values($product->getExtensionAttributes()->getConfigurableProductLinks());
        self::assertEquals(
            $associatedProductIds,
            $configurableProductLinks,
            'Product links are not available in the registry'
        );

        /** @var $product \Magento\Catalog\Api\Data\ProductInterface */
        $product = $objectManager->get(ProductRepositoryInterface::class)->getById(1, false, null, true);
        $configurableProductLinks = array_values($product->getExtensionAttributes()->getConfigurableProductLinks());
        self::assertEquals(
            $associatedProductIds,
            $configurableProductLinks,
            'Product links are not available in the database'
        );
    }

    /**
     * Retrieve configurable attribute instance
     *
     * @return \Magento\Catalog\Model\Entity\Attribute
     */
    protected function _getConfigurableAttribute()
    {
        return \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Catalog\Model\Entity\Attribute::class
        )->loadByCode(
            \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
                \Magento\Eav\Model\Config::class
            )->getEntityType(
                'catalog_product'
            )->getId(),
            'test_configurable'
        );
    }
}

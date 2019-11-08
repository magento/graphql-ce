<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Controller\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Model\AttributeSetSearchResults;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Data\Collection;
use Magento\Framework\Registry;
use Magento\Catalog\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\Entity\Type;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Logger\Monolog as MagentoMonologLogger;
use Magento\TestFramework\Response;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * Integration test for product view front action.
 *
 * @magentoAppArea frontend
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ViewTest extends AbstractController
{
    /**
     * @var ProductRepositoryInterface $productRepository
     */
    private $productRepository;

    /**
     * @var AttributeSetRepositoryInterface $attributeSetRepository
     */
    private $attributeSetRepository;

    /**
     * @var ProductAttributeRepositoryInterface $attributeSetRepository
     */
    private $attributeRepository;

    /**
     * @var Type $productEntityType
     */
    private $productEntityType;

    /** @var Registry */
    private $registry;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->productRepository = $this->_objectManager->create(ProductRepositoryInterface::class);
        $this->attributeSetRepository = $this->_objectManager->create(AttributeSetRepositoryInterface::class);
        $this->attributeRepository = $this->_objectManager->create(ProductAttributeRepositoryInterface::class);
        $this->productEntityType = $this->_objectManager->create(Type::class)
            ->loadByCode(Product::ENTITY);
        $this->registry = $this->_objectManager->get(Registry::class);
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/Catalog/controllers/_files/products.php
     * @magentoConfigFixture current_store catalog/seo/product_canonical_tag 1
     * @return void
     */
    public function testViewActionWithCanonicalTag(): void
    {
        $this->markTestSkipped(
            'MAGETWO-40724: Canonical url from tests sometimes does not equal canonical url from action'
        );
        $this->dispatch('catalog/product/view/id/1/');

        $this->assertContains(
            '<link  rel="canonical" href="http://localhost/index.php/catalog/product/view/_ignore_category/1/id/1/" />',
            $this->getResponse()->getBody()
        );
    }

    /**
     * View product with custom attribute when attribute removed from it.
     *
     * It tests that after changing product attribute set from Default to Custom
     * there are no warning messages in log in case Custom not contains attribute from Default.
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple_with_country_of_manufacture.php
     * @magentoDataFixture Magento/Catalog/_files/attribute_set_based_on_default_without_country_of_manufacture.php
     * @return void
     */
    public function testViewActionCustomAttributeSetWithoutCountryOfManufacture(): void
    {
        /** @var MockObject|LoggerInterface $logger */
        $logger = $this->setupLoggerMock();

        $product = $this->getProductBySku('simple_with_com');
        $attributeSetCustom = $this->getProductAttributeSetByName('custom_attribute_set_wout_com');
        $product->setAttributeSetId($attributeSetCustom->getAttributeSetId());
        $this->productRepository->save($product);

        /** @var ProductAttributeInterface $attributeCountryOfManufacture */
        $attributeCountryOfManufacture = $this->attributeRepository->get('country_of_manufacture');
        $logger->expects($this->never())
            ->method('warning')
            ->with(
                "Attempt to load value of nonexistent EAV attribute",
                [
                    'attribute_id' =>  $attributeCountryOfManufacture->getAttributeId(),
                    'entity_type' => ProductInterface::class,
                ]
            );

        $this->dispatch(sprintf('catalog/product/view/id/%s/', $product->getId()));
    }

    /**
     * Setup logger mock to check there are no warning messages logged.
     *
     * @return MockObject
     */
    private function setupLoggerMock() : MockObject
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->_objectManager->addSharedInstance($logger, MagentoMonologLogger::class);

        return $logger;
    }

    /**
     * Get product instance by sku.
     *
     * @param string $sku
     * @return Product
     */
    private function getProductBySku(string $sku): Product
    {
        return $this->productRepository->get($sku);
    }

    /**
     * Get product attribute set by name.
     *
     * @param string $attributeSetName
     * @return Set|null
     */
    private function getProductAttributeSetByName(string $attributeSetName): ?Set
    {
        /** @var SortOrderBuilder $sortOrderBuilder */
        $sortOrderBuilder = $this->_objectManager->create(SortOrderBuilder::class);
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->_objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->addFilter('attribute_set_name', $attributeSetName);
        $searchCriteriaBuilder->addFilter('entity_type_id', $this->productEntityType->getId());
        $attributeSetIdSortOrder = $sortOrderBuilder
            ->setField('attribute_set_id')
            ->setDirection(Collection::SORT_ORDER_DESC)
            ->create();
        $searchCriteriaBuilder->addSortOrder($attributeSetIdSortOrder);
        $searchCriteriaBuilder->setPageSize(1);
        $searchCriteriaBuilder->setCurrentPage(1);

        /** @var AttributeSetSearchResults $searchResult */
        $searchResult = $this->attributeSetRepository->getList($searchCriteriaBuilder->create());
        $items = $searchResult->getItems();

        if (count($items) > 0) {
            return reset($items);
        }

        return null;
    }

    /**
     * @magentoDataFixture Magento/Quote/_files/is_not_salable_product.php
     * @return void
     */
    public function testDisabledProductInvisibility(): void
    {
        $product = $this->productRepository->get('simple-99');
        $this->dispatch(sprintf('catalog/product/view/id/%s/', $product->getId()));

        $this->assert404NotFound();
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/second_product_simple.php
     * @dataProvider productVisibilityDataProvider
     * @param int $visibility
     * @return void
     */
    public function testProductVisibility(int $visibility): void
    {
        $product = $this->updateProductVisibility('simple2', $visibility);
        $this->dispatch(sprintf('catalog/product/view/id/%s/', $product->getId()));

        $this->assertProductIsVisible($product);
    }

    /**
     * @return array
     */
    public function productVisibilityDataProvider(): array
    {
        return [
            'catalog_search' => [Visibility::VISIBILITY_BOTH],
            'search' => [Visibility::VISIBILITY_IN_SEARCH],
            'catalog' => [Visibility::VISIBILITY_IN_CATALOG],
        ];
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/simple_products_not_visible_individually.php
     */
    public function testProductNotVisibleIndividually(): void
    {
        $product = $this->updateProductVisibility('simple_not_visible_1', Visibility::VISIBILITY_NOT_VISIBLE);
        $this->dispatch(sprintf('catalog/product/view/id/%s/', $product->getId()));

        $this->assert404NotFound();
    }

    /**
     * @inheritdoc
     */
    public function assert404NotFound()
    {
        parent::assert404NotFound();

        $this->assertNull($this->registry->registry('current_product'));
    }

    /**
     * Assert that product is available in storefront
     *
     * @param ProductInterface $product
     * @return void
     */
    private function assertProductIsVisible(ProductInterface $product): void
    {
        $this->assertEquals(
            Response::STATUS_CODE_200,
            $this->getResponse()->getHttpResponseCode(),
            'Wrong response code is returned'
        );
        $this->assertEquals(
            $product->getSku(),
            $this->registry->registry('current_product')->getSku(),
            'Wrong product is registered'
        );
    }

    /**
     * Update product visibility
     *
     * @param string $sku
     * @param int $visibility
     * @return ProductInterface
     */
    private function updateProductVisibility(string $sku, int $visibility): ProductInterface
    {
        $product = $this->productRepository->get($sku);
        $product->setVisibility($visibility);

        return $this->productRepository->save($product);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Downloadable\Test\TestCase;

use Magento\Catalog\Test\Fixture\Category;
use Magento\Catalog\Test\Page\Adminhtml\CatalogProductIndex;
use Magento\Catalog\Test\Page\Adminhtml\CatalogProductNew;
use Magento\Downloadable\Test\Fixture\DownloadableProduct;
use Magento\Mtf\TestCase\Injectable;
use Magento\Mtf\Util\Command\Cli\EnvWhitelist;

/**
 * Steps:
 * 1. Log in to Backend.
 * 2. Navigate to Products > Catalog.
 * 3. Start to create new Downloadable product.
 * 4. Fill in data according to data set.
 * 5. Fill Downloadable Information tab according to data set.
 * 6. Save product.
 * 7. Verify created product.
 *
 * @group Downloadable_Product
 * @ZephyrId MAGETWO-23425
 */
class CreateDownloadableProductEntityTest extends Injectable
{
    /* tags */
    const TEST_TYPE = 'acceptance_test, extended_acceptance_test';
    const MVP = 'yes';
    /* end tags */

    /**
     * Fixture category
     *
     * @var Category
     */
    protected $category;

    /**
     * Product page with a grid
     *
     * @var CatalogProductIndex
     */
    protected $catalogProductIndex;

    /**
     * New product page on backend
     *
     * @var CatalogProductNew
     */
    protected $catalogProductNew;

    /**
     * DomainWhitelist CLI
     *
     * @var EnvWhitelist
     */
    private $envWhitelist;

    /**
     * Persist category
     *
     * @param Category $category
     * @return array
     */
    public function __prepare(Category $category)
    {
        $category->persist();
        return [
            'category' => $category
        ];
    }

    /**
     * Filling objects of the class
     *
     * @param Category $category
     * @param CatalogProductIndex $catalogProductIndexNewPage
     * @param CatalogProductNew $catalogProductNewPage
     * @param EnvWhitelist $envWhitelist
     * @return void
     */
    public function __inject(
        Category $category,
        CatalogProductIndex $catalogProductIndexNewPage,
        CatalogProductNew $catalogProductNewPage,
        EnvWhitelist $envWhitelist
    ) {
        $this->category = $category;
        $this->catalogProductIndex = $catalogProductIndexNewPage;
        $this->catalogProductNew = $catalogProductNewPage;
        $this->envWhitelist = $envWhitelist;
    }

    /**
     * Test create downloadable product
     *
     * @param DownloadableProduct $product
     * @param Category $category
     * @return void
     */
    public function test(DownloadableProduct $product, Category $category)
    {
        // Steps
        $this->envWhitelist->addHost('example.com');
        $this->catalogProductIndex->open();
        $this->catalogProductIndex->getGridPageActionBlock()->addProduct('downloadable');
        $productBlockForm = $this->catalogProductNew->getProductForm();
        $productBlockForm->fill($product, null, $category);
        $this->catalogProductNew->getFormPageActions()->save();
    }

    /**
     * Clean data after running test.
     *
     * @return void
     */
    protected function tearDown()
    {
        $this->envWhitelist->removeHost('example.com');
    }
}

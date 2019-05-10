<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\LayeredNavigation\Test\Block;

use Magento\Catalog\Test\Fixture\Category;
use Magento\Mtf\Block\Block;
use Magento\Mtf\Client\Locator;

/**
 * Catalog layered navigation view block.
 */
class Navigation extends Block
{
    /**
     * Locator for loaded "narrow-by-list" block.
     *
     * @var string
     */
    protected $loadedNarrowByList = '#narrow-by-list[role="tablist"]';

    /**
     * Locator value for "Clear All" link.
     *
     * @var string
     */
    protected $clearAll = '.action.clear';

    /**
     * Locator value for correspondent Attribute filter option.
     *
     * @var string
     */
    protected $optionTitle = './/div[@class="filter-options-title" and contains(text(),"%s")]';

    /**
     * Locator value for correspondent "Filter" link.
     *
     * @var string
     */
    protected $filterLink = './/div[@class="filter-options-title" and contains(text(),"%s")]/following-sibling::div//a';

    /**
     * Locator value for "Expand Filter" button.
     *
     * @var string
     */
    protected $expandFilterButton = '[data]';

    /**
     * Locator for category name.
     *
     * @var string
     */
    private $categoryName = './/li[@class="item"]//a[contains(text(),"%s")]';

    /**
     * Locator for element with product quantity.
     *
     * @var string
     */
    private $productQty = '/following-sibling::span[contains(text(), "%s")]';

    /**
     * Selector for child element with product quantity.
     *
     * @var string
     */
    private $productQtyInCategory = '/span[contains(text(), "%s")]';

    /**
     * Remove all applied filters.
     *
     * @return void
     */
    public function clearAll()
    {
        $this->_rootElement->find($this->clearAll)->click();
    }

    /**
     * Get all available filters.
     *
     * @return array
     */
    public function getFilters()
    {
        $this->waitForElementVisible($this->loadedNarrowByList);

        $options = $this->_rootElement->getElements(sprintf($this->optionTitle, ''), Locator::SELECTOR_XPATH);
        $data = [];
        foreach ($options as $option) {
            $data[] = strtoupper($option->getText());
        }

        return $data;
    }

    /**
     * Apply Layered Navigation filter.
     *
     * @param string $filter
     * @param string $linkPattern
     * @return void
     * @throws \Exception
     */
    public function applyFilter($filter, $linkPattern)
    {
        $links = sprintf($this->filterLink, $filter);
        $this->openFilterContainer($filter, $links);

        $links = $this->_rootElement->getElements($links, Locator::SELECTOR_XPATH);
        foreach ($links as $link) {
            if (preg_match($linkPattern, trim($link->getText()))) {
                $link->click();
                return;
            }
        }
        throw new \Exception("Can't find {$filter} filter link by pattern: {$linkPattern}");
    }

    /**
     * Check that category with product quantity can be displayed on layered navigation.
     *
     * @param Category $category
     * @param int $qty
     * @return bool
     */
    public function isCategoryVisible(Category $category, $qty)
    {
        $link = sprintf($this->categoryName, $category->getName());

        if (!$this->_rootElement->find($link, Locator::SELECTOR_XPATH)->isVisible()) {
            $this->openFilterContainer('Category', $link);
            return $this->_rootElement->find(
                $link . sprintf($this->productQtyInCategory, $qty),
                Locator::SELECTOR_XPATH
            )->isVisible();
        } else {
            return $this->_rootElement->find(
                $link . sprintf($this->productQty, $qty),
                Locator::SELECTOR_XPATH
            )->isVisible();
        }
    }

    /**
     * Get Layered Navigation filter options.
     *
     * @param string $attributeLabel
     * @return array
     */
    public function getFilterContents($attributeLabel)
    {
        $data = [];

        if (trim($attributeLabel) === '') {
            return $data;
        }

        $link = sprintf($this->filterLink, $attributeLabel);
        $this->openFilterContainer($attributeLabel, $link);

        $optionContents = $this->_rootElement->getElements($link, Locator::SELECTOR_XPATH);

        foreach ($optionContents as $optionContent) {
            $data[] = trim(strtoupper($optionContent->getText()));
        }

        return $data;
    }

    /**
     * Open filter container.
     *
     * @param string $filter
     * @param string $link
     * @return void
     */
    private function openFilterContainer($filter, $link)
    {
        $expandFilterButton = sprintf($this->optionTitle, $filter);

        $this->waitForElementVisible($this->loadedNarrowByList);
        if (!$this->_rootElement->find($link, Locator::SELECTOR_XPATH)->isVisible()) {
            $this->_rootElement->find($expandFilterButton, Locator::SELECTOR_XPATH)->click();
        }
    }
}

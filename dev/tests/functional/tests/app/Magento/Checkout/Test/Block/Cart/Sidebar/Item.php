<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Checkout\Test\Block\Cart\Sidebar;

use Magento\Checkout\Test\Block\Cart\Sidebar;

/**
 * Product item block on mini Cart.
 */
class Item extends Sidebar
{
    /**
     * Selector for "Remove item" button.
     *
     * @var string
     */
    protected $removeItem = '.action.delete';

    /**
     * Selector for "Edit item" button.
     *
     * @var string
     */
    protected $editItem = '.action.edit';

    /**
     * Selector for confirm.
     *
     * @var string
     */
    protected $confirmModal = '.confirm._show[data-role=modal]';

    /**
     * CSS selector for qty field.
     *
     * @var string
     */
    protected $qty = 'input.cart-item-qty';

    /**
     * CSS selector for price field.
     *
     * @var string
     */
    protected $price = '.minicart-price .price';

    /**
     * CSS selector for update button.
     *
     * @var string
     */
    private $updateButton = 'button.update-cart-item';

    /**
     * Remove product item from mini cart.
     *
     * @return void
     */
    public function removeItemFromMiniCart()
    {
        $this->waitForDeleteButtonVisible();
        $this->_rootElement->find($this->removeItem)->click();
        $element = $this->browser->find($this->confirmModal);
        /** @var \Magento\Ui\Test\Block\Adminhtml\Modal $modal */
        $modal = $this->blockFactory->create(\Magento\Ui\Test\Block\Adminhtml\Modal::class, ['element' => $element]);
        $modal->acceptAlert();
        $modal->waitModalWindowToDisappear();
    }

    /**
     * Wait for Delete button is visible in the block.
     *
     * @return bool|null
     */
    private function waitForDeleteButtonVisible()
    {
        $rootElement = $this->_rootElement;
        $deleteButtonSelector = $this->removeItem;
        return $rootElement->waitUntil(
            function () use ($rootElement, $deleteButtonSelector) {
                $element = $rootElement->find($deleteButtonSelector);
                return $element->isVisible() ? true : null;
            }
        );
    }

    /**
     * Click "Edit item" button.
     *
     * @return void
     */
    public function clickEditItem()
    {
        $this->_rootElement->find($this->editItem)->click();
    }

    /**
     * Edit qty.
     *
     * @param array $checkoutData
     * @return void
     */
    public function editQty(array $checkoutData)
    {
        if (isset($checkoutData['qty'])) {
            $this->_rootElement->find($this->qty)->setValue($checkoutData['qty']);
            $this->_rootElement->find($this->updateButton)->click();
        }
    }

    /**
     * Get product quantity.
     *
     * @return string
     */
    public function getQty()
    {
        $rootElement = $this->_rootElement;
        $qtySelector = $this->qty;
        $this->browser->waitUntil(
            function () use ($rootElement, $qtySelector) {
                return $rootElement->find($qtySelector)->isVisible() ? true : null;
            }
        );
        return $this->_rootElement->find($this->qty)->getValue();
    }

    /**
     * Get product price.
     *
     * @return string
     */
    public function getPrice()
    {
        $price = $this->_rootElement->find($this->price)->getText();
        return parent::escapeCurrency($price);
    }
}

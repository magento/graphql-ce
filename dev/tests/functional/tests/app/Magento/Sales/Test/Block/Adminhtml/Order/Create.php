<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Sales\Test\Block\Adminhtml\Order;

use Magento\Mtf\Block\Block;
use Magento\Mtf\Client\Locator;
use Magento\Mtf\Fixture\FixtureInterface;
use Magento\Mtf\Fixture\InjectableFixture;

/**
 * Adminhtml sales order create block.
 */
class Create extends Block
{
    /**
     * Sales order create items block.
     *
     * @var string
     */
    protected $itemsBlock = '#order-items';

    /**
     * Sales order create search products block.
     *
     * @var string
     */
    protected $gridBlock = '#order-search';

    /**
     * Sales order create billing address block.
     *
     * @var string
     */
    protected $billingAddressBlock = '#order-billing_address';

    /**
     * Sales order create shipping address block.
     *
     * @var string
     */
    protected $shippingAddressBlock = '#order-shipping_address';

    /**
     * Sales order create payment method block.
     *
     * @var string
     */
    protected $billingMethodBlock = '#order-billing_method';

    /**
     * Sales order create shipping method block.
     *
     * @var string
     */
    protected $shippingMethodBlock = '#order-shipping_method';

    /**
     * Sales order create totals block.
     *
     * @var string
     */
    protected $totalsBlock = '#order-totals';

    /**
     * Backend abstract block.
     *
     * @var string
     */
    protected $templateBlock = './ancestor::body';

    /**
     * Order items grid block.
     *
     * @var string
     */
    protected $orderItemsGrid = '#order-items_grid';

    /**
     * Update items button.
     *
     * @var string
     */
    protected $updateItems = '[onclick="order.itemsUpdate()"]';

    /**
     * 'Add Selected Product(s) to Order' button.
     *
     * @var string
     */
    protected $addSelectedProducts = 'button[onclick="order.productGridAddSelected()"]';

    /**
     * Sales order create account information block.
     *
     * @var string
     */
    protected $accountInformationBlock = '#order-form_account';

    /**
     * Payment and Shipping methods block.
     *
     * @var string
     */
    protected $orderMethodsSelector = '#order-methods';

    /**
     * Page header.
     *
     * @var string
     */
    protected $header = 'header';

    /**
     * Save credit card check box.
     *
     * @var string
     */
    protected $vaultCheckbox = '#%s_vault';

    /**
     * Getter for order selected products grid.
     *
     * @return \Magento\Sales\Test\Block\Adminhtml\Order\Create\Items
     */
    public function getItemsBlock()
    {
        return $this->blockFactory->create(
            \Magento\Sales\Test\Block\Adminhtml\Order\Create\Items::class,
            ['element' => $this->_rootElement->find($this->itemsBlock, Locator::SELECTOR_CSS)]
        );
    }

    /**
     * Get sales order create billing address block.
     *
     * @return \Magento\Sales\Test\Block\Adminhtml\Order\Create\Billing\Address
     */
    public function getBillingAddressBlock()
    {
        return $this->blockFactory->create(
            \Magento\Sales\Test\Block\Adminhtml\Order\Create\Billing\Address::class,
            ['element' => $this->_rootElement->find($this->billingAddressBlock, Locator::SELECTOR_CSS)]
        );
    }

    /**
     * Get sales order create billing address block.
     *
     * @return \Magento\Sales\Test\Block\Adminhtml\Order\Create\Shipping\Address
     */
    protected function getShippingAddressBlock()
    {
        return $this->blockFactory->create(
            \Magento\Sales\Test\Block\Adminhtml\Order\Create\Shipping\Address::class,
            ['element' => $this->_rootElement->find($this->shippingAddressBlock, Locator::SELECTOR_CSS)]
        );
    }

    /**
     * Get sales order create payment method block.
     *
     * @return \Magento\Sales\Test\Block\Adminhtml\Order\Create\Billing\Method
     */
    public function getBillingMethodBlock()
    {
        return $this->blockFactory->create(
            \Magento\Sales\Test\Block\Adminhtml\Order\Create\Billing\Method::class,
            ['element' => $this->_rootElement->find($this->billingMethodBlock, Locator::SELECTOR_CSS)]
        );
    }

    /**
     * Get sales order create shipping method block.
     *
     * @return \Magento\Sales\Test\Block\Adminhtml\Order\Create\Shipping\Method
     */
    protected function getShippingMethodBlock()
    {
        return $this->blockFactory->create(
            \Magento\Sales\Test\Block\Adminhtml\Order\Create\Shipping\Method::class,
            ['element' => $this->_rootElement->find($this->shippingMethodBlock, Locator::SELECTOR_CSS)]
        );
    }

    /**
     * Get sales order create totals block.
     *
     * @return \Magento\Sales\Test\Block\Adminhtml\Order\Create\Totals
     */
    protected function getTotalsBlock()
    {
        return $this->blockFactory->create(
            \Magento\Sales\Test\Block\Adminhtml\Order\Create\Totals::class,
            ['element' => $this->_rootElement->find($this->totalsBlock, Locator::SELECTOR_CSS)]
        );
    }

    /**
     * Get backend abstract block.
     *
     * @return \Magento\Backend\Test\Block\Template
     */
    public function getTemplateBlock()
    {
        return $this->blockFactory->create(
            \Magento\Backend\Test\Block\Template::class,
            ['element' => $this->_rootElement->find($this->templateBlock, Locator::SELECTOR_XPATH)]
        );
    }

    /**
     * Get sales order create search products block.
     *
     * @return \Magento\Sales\Test\Block\Adminhtml\Order\Create\Search\Grid
     */
    public function getGridBlock()
    {
        return $this->blockFactory->create(
            \Magento\Sales\Test\Block\Adminhtml\Order\Create\Search\Grid::class,
            ['element' => $this->_rootElement->find($this->gridBlock, Locator::SELECTOR_CSS)]
        );
    }

    /**
     * Get sales order create account information block.
     *
     * @return \Magento\Sales\Test\Block\Adminhtml\Order\Create\Form\Account
     */
    public function getAccountBlock()
    {
        return $this->blockFactory->create(
            \Magento\Sales\Test\Block\Adminhtml\Order\Create\Form\Account::class,
            ['element' => $this->_rootElement->find($this->accountInformationBlock, Locator::SELECTOR_CSS)]
        );
    }

    /**
     * Wait display order items grid.
     *
     * @return void
     */
    public function waitOrderItemsGrid()
    {
        $this->waitForElementVisible($this->orderItemsGrid);
    }

    /**
     * Update product data in sales.
     *
     * @param array $products
     * @return void
     */
    public function updateProductsData(array $products)
    {
        /** @var \Magento\Sales\Test\Block\Adminhtml\Order\Create\Items $items */
        $items = $this->blockFactory->create(
            \Magento\Sales\Test\Block\Adminhtml\Order\Create\Items::class,
            ['element' => $this->_rootElement->find($this->itemsBlock)]
        );
        foreach ($products as $product) {
            $items->getItemProductByName($product->getName())->fillCheckoutData($product->getCheckoutData());
        }
        $this->updateItems();
    }

    /**
     * Update product items.
     *
     * @return void
     */
    public function updateItems()
    {
        $this->_rootElement->find($this->updateItems)->click();
        $this->getTemplateBlock()->waitLoader();
    }

    /**
     * Fill Billing Address.
     *
     * @param FixtureInterface $billingAddress
     * @param string $saveAddress [optional]
     * @param bool $setShippingAddress [optional]
     * @return void
     */
    public function fillBillingAddress(
        FixtureInterface $billingAddress,
        $saveAddress = 'No',
        $setShippingAddress = true
    ) {
        if ($setShippingAddress !== false) {
            $this->getShippingAddressBlock()->uncheckSameAsBillingShippingAddress();
        }
        $this->getBillingAddressBlock()->fill($billingAddress);
        $this->getBillingAddressBlock()->saveInAddressBookBillingAddress($saveAddress);
        $this->getTemplateBlock()->waitLoader();
        if ($setShippingAddress) {
            $this->getShippingAddressBlock()->setSameAsBillingShippingAddress();
            $this->getTemplateBlock()->waitLoader();
        }
    }

    /**
     * Fill Shipping Address.
     *
     * @param FixtureInterface $shippingAddress
     * @return void
     */
    public function fillShippingAddress(FixtureInterface $shippingAddress)
    {
        $this->getShippingAddressBlock()->fill($shippingAddress);
        $this->getTemplateBlock()->waitLoader();
    }

    /**
     * Select shipping method.
     *
     * @param array $shippingMethod
     * @return void
     */
    public function selectShippingMethod(array $shippingMethod)
    {
        $this->getShippingMethodBlock()->selectShippingMethod($shippingMethod);
        $this->getTemplateBlock()->waitLoader();
    }

    /**
     * Select payment method.
     *
     * @param array $paymentCode
     * @param InjectableFixture|null $creditCard
     */
    public function selectPaymentMethod(array $paymentCode, InjectableFixture $creditCard = null)
    {
        $this->getTemplateBlock()->waitLoader();
        $this->_rootElement->find($this->orderMethodsSelector)->click();
        $this->getBillingMethodBlock()->selectPaymentMethod($paymentCode, $creditCard);
        $this->_rootElement->click();
        $this->getTemplateBlock()->waitLoader();
    }

    /**
     * Submit order.
     *
     * @return void
     */
    public function submitOrder()
    {
        $this->getTotalsBlock()->submitOrder();
    }

    /**
     * Click "Add Selected Product(s) to Order" button.
     *
     * @return void
     */
    public function addSelectedProductsToOrder()
    {
        $this->_rootElement->find($this->addSelectedProducts)->click();
    }

    /**
     * Save credit card.
     *
     * @param string $paymentMethod
     * @param string $creditCardSave
     * @return void
     */
    public function saveCreditCard($paymentMethod, $creditCardSave)
    {
        $saveCard = sprintf($this->vaultCheckbox, $paymentMethod);
        $this->_rootElement->find($saveCard, Locator::SELECTOR_CSS, 'checkbox')->setValue($creditCardSave);
    }

    /**
     * Select vault payment token radio button
     * @param string $selector
     * @return void
     */
    public function selectVaultToken($selector)
    {
        $selector = '[id^="' . $selector . '"]';
        $this->_rootElement->find($selector)->click();
    }
}

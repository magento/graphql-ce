<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Customer\Block\Adminhtml\Edit\Tab;

use Magento\Catalog\Model\Product;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Directory\Model\Currency;

/**
 * Adminhtml customer orders grid block
 *
 * @api
 * @since 100.0.2
 */
class Cart extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\Data\CollectionFactory
     */
    protected $_dataCollectionFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $quote = null;

    /**
     * @var string
     */
    protected $_parentTemplate;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Data\CollectionFactory $dataCollectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Data\CollectionFactory $dataCollectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        array $data = []
    ) {
        $this->_dataCollectionFactory = $dataCollectionFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->quoteRepository = $quoteRepository;
        $this->quoteFactory = $quoteFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setUseAjax(true);
        $this->_parentTemplate = $this->getTemplate();
        $this->setTemplate('Magento_Customer::tab/cart.phtml');
    }

    /**
     * Prepare grid
     *
     * @return void
     */
    protected function _prepareGrid()
    {
        $this->setId('customer_cart_grid' . $this->getWebsiteId());
        parent::_prepareGrid();
    }

    /**
     * Prepare collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $quote = $this->getQuote();

        if ($quote) {
            $collection = $quote->getItemsCollection(false);
        } else {
            $collection = $this->_dataCollectionFactory->create();
        }

        $collection->addFieldToFilter('parent_item_id', ['null' => true]);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn('product_id', ['header' => __('ID'), 'index' => 'product_id', 'width' => '100px']);

        $this->addColumn(
            'name',
            [
                'header' => __('Product'),
                'index' => 'name',
                'renderer' => \Magento\Customer\Block\Adminhtml\Edit\Tab\View\Grid\Renderer\Item::class
            ]
        );

        $this->addColumn('sku', ['header' => __('SKU'), 'index' => 'sku', 'width' => '100px']);

        $this->addColumn(
            'qty',
            ['header' => __('Quantity'), 'index' => 'qty', 'type' => 'number', 'width' => '60px']
        );

        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'index' => 'price',
                'type' => 'currency',
                'currency_code' => $this->getQuote()->getQuoteCurrencyCode(),
                'rate' => $this->getQuote()->getBaseToQuoteRate(),
            ]
        );

        $this->addColumn(
            'total',
            [
                'header' => __('Total'),
                'index' => 'row_total',
                'type' => 'currency',
                'currency_code' => $this->getQuote()->getQuoteCurrencyCode(),
                'rate' => 1,
            ]
        );

        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'index' => 'quote_item_id',
                'renderer' => \Magento\Customer\Block\Adminhtml\Grid\Renderer\Multiaction::class,
                'filter' => false,
                'sortable' => false,
                'actions' => [
                    [
                        'caption' => __('Configure'),
                        'url' => 'javascript:void(0)',
                        'process' => 'configurable',
                        'control_object' => $this->getJsObjectName() . 'cartControl',
                    ],
                    [
                        'caption' => __('Delete'),
                        'url' => '#',
                        'onclick' => 'return ' . $this->getJsObjectName() . 'cartControl.removeItem($item_id);'
                    ],
                ]
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Gets customer assigned to this block
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function getGridUrl()
    {
        return $this->getUrl('customer/*/cart', ['_current' => true, 'website_id' => $this->getWebsiteId()]);
    }

    /**
     * Gets grid parent html
     *
     * @return string
     */
    public function getGridParentHtml()
    {
        $templateName = $this->resolver->getTemplateFileName($this->_parentTemplate, ['_relative' => true]);
        return $this->fetchView($templateName);
    }

    /**
     * @inheritdoc
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('catalog/product/edit', ['id' => $row->getProductId()]);
    }

    /**
     * Get the quote of the cart
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote()
    {
        if (null === $this->quote) {
            $customerId = $this->getCustomerId();
            $storeIds = $this->_storeManager->getWebsite($this->getWebsiteId())->getStoreIds();

            try {
                $this->quote = $this->quoteRepository->getForCustomer($customerId, $storeIds);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->quote = $this->quoteFactory->create()->setSharedStoreIds($storeIds);
            }
        }
        return $this->quote;
    }
}

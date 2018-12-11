<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogSearch\Controller\Advanced;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\CatalogSearch\Model\Advanced as ModelAdvanced;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\UrlFactory;

/**
 * Advanced search result.
 */
class Result extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * Url factory
     *
     * @var UrlFactory
     */
    protected $_urlFactory;

    /**
     * Catalog search advanced
     *
     * @var ModelAdvanced
     */
    protected $_catalogSearchAdvanced;

    /**
     * Construct
     *
     * @param Context $context
     * @param ModelAdvanced $catalogSearchAdvanced
     * @param UrlFactory $urlFactory
     */
    public function __construct(
        Context $context,
        ModelAdvanced $catalogSearchAdvanced,
        UrlFactory $urlFactory
    ) {
        parent::__construct($context);
        $this->_catalogSearchAdvanced = $catalogSearchAdvanced;
        $this->_urlFactory = $urlFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->_catalogSearchAdvanced->addFilters($this->getRequest()->getQueryValue());
            $this->_view->loadLayout();
            $this->_view->renderLayout();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $defaultUrl = $this->_urlFactory->create()
                ->addQueryParams($this->getRequest()->getQueryValue())
                ->getUrl('*/*/');
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($this->_redirect->error($defaultUrl));
            return $resultRedirect;
        }
    }
}

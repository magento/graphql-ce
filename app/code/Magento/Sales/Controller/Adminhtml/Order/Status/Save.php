<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Controller\Adminhtml\Order\Status;

class Save extends \Magento\Sales\Controller\Adminhtml\Order\Status
{
    /**
     * Save status form processing
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $isNew = $this->getRequest()->getParam('is_new');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $statusCode = $this->getRequest()->getParam('status');

            //filter tags in labels/status
            /** @var $filterManager \Magento\Framework\Filter\FilterManager */
            $filterManager = $this->_objectManager->get(\Magento\Framework\Filter\FilterManager::class);
            if ($isNew) {
                $statusCode = $data['status'] = $filterManager->stripTags($data['status']);
            }
            $data['label'] = $filterManager->stripTags($data['label']);
            if (!isset($data['store_labels'])) {
                $data['store_labels'] = [];
            }

            foreach ($data['store_labels'] as &$label) {
                $label = $filterManager->stripTags($label);
            }

            $status = $this->_objectManager->create(\Magento\Sales\Model\Order\Status::class)->load($statusCode);
            // check if status exist
            if ($isNew && $status->getStatus()) {
                $this->messageManager
                    ->addErrorMessage(__('We found another order status with the same order status code.'));
                $this->_getSession()->setFormData($data);
                return $resultRedirect->setPath('sales/*/new');
            }

            $status->setData($data)->setStatus($statusCode);

            try {
                $status->save();
                $this->messageManager->addSuccessMessage(__('You saved the order status.'));
                return $resultRedirect->setPath('sales/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('We can\'t add the order status right now.')
                );
            }
            $this->_getSession()->setFormData($data);
            return $this->getRedirect($resultRedirect, $isNew);
        }
        return $resultRedirect->setPath('sales/*/');
    }

    /**
     * @param \Magento\Backend\Model\View\Result\Redirect $resultRedirect
     * @param bool $isNew
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    private function getRedirect(\Magento\Backend\Model\View\Result\Redirect $resultRedirect, $isNew)
    {
        if ($isNew) {
            return $resultRedirect->setPath('sales/*/new');
        } else {
            return $resultRedirect->setPath('sales/*/edit', ['status' => $this->getRequest()->getParam('status')]);
        }
    }
}

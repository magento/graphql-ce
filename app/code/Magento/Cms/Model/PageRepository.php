<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Cms\Model;

use Magento\Cms\Api\Data;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Cms\Model\ResourceModel\Page as ResourcePage;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Authorization\Model\UserContextInterface;

/**
 * Class PageRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PageRepository implements PageRepositoryInterface
{
    /**
     * @var ResourcePage
     */
    protected $resource;

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var PageCollectionFactory
     */
    protected $pageCollectionFactory;

    /**
     * @var Data\PageSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var \Magento\Cms\Api\Data\PageInterfaceFactory
     */
    protected $dataPageFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @param ResourcePage $resource
     * @param PageFactory $pageFactory
     * @param Data\PageInterfaceFactory $dataPageFactory
     * @param PageCollectionFactory $pageCollectionFactory
     * @param Data\PageSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param CollectionProcessorInterface $collectionProcessor
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResourcePage $resource,
        PageFactory $pageFactory,
        Data\PageInterfaceFactory $dataPageFactory,
        PageCollectionFactory $pageCollectionFactory,
        Data\PageSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        $this->resource = $resource;
        $this->pageFactory = $pageFactory;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataPageFactory = $dataPageFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor ?: $this->getCollectionProcessor();
    }

    /**
     * Get user context.
     *
     * @return UserContextInterface
     */
    private function getUserContext(): UserContextInterface
    {
        if (!$this->userContext) {
            $this->userContext = ObjectManager::getInstance()->get(UserContextInterface::class);
        }

        return $this->userContext;
    }

    /**
     * Get authorization service.
     *
     * @return AuthorizationInterface
     */
    private function getAuthorization(): AuthorizationInterface
    {
        if (!$this->authorization) {
            $this->authorization = ObjectManager::getInstance()->get(AuthorizationInterface::class);
        }

        return $this->authorization;
    }

    /**
     * Save Page data
     *
     * @param \Magento\Cms\Api\Data\PageInterface|Page $page
     * @return Page
     * @throws CouldNotSaveException
     */
    public function save(\Magento\Cms\Api\Data\PageInterface $page)
    {
        if ($page->getStoreId() === null) {
            $storeId = $this->storeManager->getStore()->getId();
            $page->setStoreId($storeId);
        }
        try {
            //Validate changing of design.
            $userType = $this->getUserContext()->getUserType();
            if ((
                    $userType === UserContextInterface::USER_TYPE_ADMIN
                    || $userType === UserContextInterface::USER_TYPE_INTEGRATION
                )
                && !$this->getAuthorization()->isAllowed('Magento_Cms::save_design')
            ) {
                if (!$page->getId()) {
                    $page->setLayoutUpdateXml(null);
                    $page->setPageLayout(null);
                    $page->setCustomTheme(null);
                    $page->setCustomLayoutUpdateXml(null);
                    $page->setCustomThemeTo(null);
                    $page->setCustomThemeFrom(null);
                } else {
                    $savedPage = $this->getById($page->getId());
                    $page->setLayoutUpdateXml($savedPage->getLayoutUpdateXml());
                    $page->setPageLayout($savedPage->getPageLayout());
                    $page->setCustomTheme($savedPage->getCustomTheme());
                    $page->setCustomLayoutUpdateXml($savedPage->getCustomLayoutUpdateXml());
                    $page->setCustomThemeTo($savedPage->getCustomThemeTo());
                    $page->setCustomThemeFrom($savedPage->getCustomThemeFrom());
                }
            }

            $this->resource->save($page);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the page: %1', $exception->getMessage()),
                $exception
            );
        }
        return $page;
    }

    /**
     * Load Page data by given Page Identity
     *
     * @param string $pageId
     * @return Page
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($pageId)
    {
        $page = $this->pageFactory->create();
        $page->load($pageId);
        if (!$page->getId()) {
            throw new NoSuchEntityException(__('The CMS page with the "%1" ID doesn\'t exist.', $pageId));
        }
        return $page;
    }

    /**
     * Load Page data collection by given search criteria
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @param \Magento\Framework\Api\SearchCriteriaInterface $criteria
     * @return \Magento\Cms\Api\Data\PageSearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $criteria)
    {
        /** @var \Magento\Cms\Model\ResourceModel\Page\Collection $collection */
        $collection = $this->pageCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        /** @var Data\PageSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * Delete Page
     *
     * @param \Magento\Cms\Api\Data\PageInterface $page
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(\Magento\Cms\Api\Data\PageInterface $page)
    {
        try {
            $this->resource->delete($page);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the page: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * Delete Page by given Page Identity
     *
     * @param string $pageId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($pageId)
    {
        return $this->delete($this->getById($pageId));
    }

    /**
     * Retrieve collection processor
     *
     * @deprecated 101.1.0
     * @return CollectionProcessorInterface
     */
    private function getCollectionProcessor()
    {
        if (!$this->collectionProcessor) {
            $this->collectionProcessor = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Magento\Cms\Model\Api\SearchCriteria\PageCollectionProcessor'
            );
        }
        return $this->collectionProcessor;
    }
}

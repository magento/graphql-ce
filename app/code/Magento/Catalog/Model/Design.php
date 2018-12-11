<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Catalog\Model;

use \Magento\Framework\TranslateInterface;

/**
 * Catalog Custom Category design Model
 *
 * @api
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Design extends \Magento\Framework\Model\AbstractModel
{
    const APPLY_FOR_PRODUCT = 1;

    const APPLY_FOR_CATEGORY = 2;

    /**
     * Design package instance
     *
     * @var \Magento\Framework\View\DesignInterface
     */
    protected $_design = null;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var TranslateInterface
     */
    private $translator;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\View\DesignInterface $design
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param TranslateInterface|null $translator
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        TranslateInterface $translator = null
    ) {
        $this->_localeDate = $localeDate;
        $this->_design = $design;
        $this->translator = $translator ?:
            \Magento\Framework\App\ObjectManager::getInstance()->get(TranslateInterface::class);
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Apply custom design
     *
     * @param string $design
     * @return $this
     */
    public function applyCustomDesign($design)
    {
        $this->_design->setDesignTheme($design);
        $this->translator->loadData(null, true);
        return $this;
    }

    /**
     * Get custom layout settings
     *
     * @param \Magento\Catalog\Model\Category|\Magento\Catalog\Model\Product $object
     * @return \Magento\Framework\DataObject
     */
    public function getDesignSettings($object)
    {
        if ($object instanceof \Magento\Catalog\Model\Product) {
            $currentCategory = $object->getCategory();
        } else {
            $currentCategory = $object;
        }

        $category = null;
        if ($currentCategory) {
            $category = $currentCategory->getParentDesignCategory($currentCategory);
        }

        if ($object instanceof \Magento\Catalog\Model\Product) {
            if ($category && $category->getCustomApplyToProducts()) {
                return $this->_mergeSettings($this->_extractSettings($category), $this->_extractSettings($object));
            } else {
                return $this->_extractSettings($object);
            }
        } else {
            return $this->_extractSettings($category);
        }
    }

    /**
     * Extract custom layout settings from category or product object
     *
     * @param \Magento\Catalog\Model\Category|\Magento\Catalog\Model\Product $object
     * @return \Magento\Framework\DataObject
     */
    protected function _extractSettings($object)
    {
        $settings = new \Magento\Framework\DataObject();
        if (!$object) {
            return $settings;
        }
        $date = $object->getCustomDesignDate();
        if (array_key_exists(
            'from',
            $date
        ) && array_key_exists(
            'to',
            $date
        ) && $this->_localeDate->isScopeDateInInterval(
            null,
            $date['from'],
            $date['to']
        )
        ) {
            $settings->setCustomDesign(
                $object->getCustomDesign()
            )->setPageLayout(
                $object->getPageLayout()
            )->setLayoutUpdates(
                (array)$object->getCustomLayoutUpdate()
            );
        }
        return $settings;
    }

    /**
     * Merge custom design settings
     *
     * @param \Magento\Framework\DataObject $categorySettings
     * @param \Magento\Framework\DataObject $productSettings
     * @return \Magento\Framework\DataObject
     */
    protected function _mergeSettings($categorySettings, $productSettings)
    {
        if ($productSettings->getCustomDesign()) {
            $categorySettings->setCustomDesign($productSettings->getCustomDesign());
        }
        if ($productSettings->getPageLayout()) {
            $categorySettings->setPageLayout($productSettings->getPageLayout());
        }
        if ($productSettings->getLayoutUpdates()) {
            $update = array_merge($categorySettings->getLayoutUpdates(), $productSettings->getLayoutUpdates());
            $categorySettings->setLayoutUpdates($update);
        }
        return $categorySettings;
    }
}

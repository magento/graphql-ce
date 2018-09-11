<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Category;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * Retrieves the sort fields data
 */
class SortFields implements ResolverInterface
{
    /**
     * @var \Magento\Catalog\Model\Config
     */
    private $catalogConfig;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    
    /**
     * @var \Magento\Catalog\Model\Category\Attribute\Source\Sortby
     */
    private $sortbyAttributeSource;

    /**
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @oaram \Magento\Catalog\Model\Category\Attribute\Source\Sortby $sortbyAttributeSource
     */
    public function __construct(
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Category\Attribute\Source\Sortby $sortbyAttributeSource
    ) {
        $this->catalogConfig = $catalogConfig;
        $this->storeManager = $storeManager;
        $this->sortbyAttributeSource = $sortbyAttributeSource;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $sortFieldsOptions = $this->sortbyAttributeSource->getAllOptions();
        array_walk(
            $sortFieldsOptions,
            function (&$option) {
                $option['label'] = (string)$option['label'];
            }
        );
        $data = [
            'default' => $this->catalogConfig->getProductListDefaultSortBy($this->storeManager->getStore()->getId()),
            'options' => $sortFieldsOptions,
        ];
        
        return $data;
    }
}

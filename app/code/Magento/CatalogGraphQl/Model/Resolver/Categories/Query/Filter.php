<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Categories\Query;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\CatalogGraphQl\Model\Resolver\Categories\DataProvider\Category;
use Magento\CatalogGraphQl\Model\Resolver\Categories\SearchResult;
use Magento\CatalogGraphQl\Model\Resolver\Categories\SearchResultFactory;
use Magento\Framework\GraphQl\Query\FieldTranslator;

/**
 * Retrieve filtered category data based off given search criteria in a format that GraphQL can interpret.
 */
class Filter
{
    /**
     * @var SearchResultFactory
     */
    private $searchResultFactory;

    /**
     * @var \Magento\Catalog\Api\CategoryListInterface
     */
    private $categoryDataProvider;

    /**
     * @var FieldTranslator
     */
    private $fieldTranslator;

    /**
     * @var \Magento\Catalog\Model\Layer\Resolver
     */
    private $layerResolver;

    /**
     * Filter constructor.
     * @param SearchResultFactory $searchResultFactory
     * @param \Magento\Catalog\Api\CategoryListInterface $categoryDataProvider
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param FieldTranslator $fieldTranslator
     */
    public function __construct(
        SearchResultFactory $searchResultFactory,
        \Magento\Catalog\Api\CategoryListInterface $categoryDataProvider,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        FieldTranslator $fieldTranslator
    ) {
        $this->searchResultFactory = $searchResultFactory;
        $this->categoryDataProvider = $categoryDataProvider;
        $this->fieldTranslator = $fieldTranslator;
        $this->layerResolver = $layerResolver;
    }

    /**
     * Filter catalog category data based off given search criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @param ResolveInfo $info
     * @param bool $isSearch
     * @return SearchResult
     */
    public function getResult(
        SearchCriteriaInterface $searchCriteria,
        ResolveInfo $info,
        bool $isSearch = false
    ): SearchResult {
        $fields = $this->getCategoryFields($info);
        $categories = $this->categoryDataProvider->getList($searchCriteria, $fields, $isSearch);
        $categoriesArray = [];
        /** @var \Magento\Catalog\Model\Category $category */
        foreach ($categories->getItems() as $category) {
            $categoriesArray[$category->getId()] = $category->getData();
            $categoriesArray[$category->getId()]['model'] = $category;
        }

        return $this->searchResultFactory->create($categories->getTotalCount(), $categoriesArray);
    }

    /**
     * Return field names for all requested category fields.
     *
     * @param ResolveInfo $info
     * @return string[]
     */
    private function getCategoryFields(ResolveInfo $info) : array
    {
        $fieldNames = [];
        foreach ($info->fieldNodes as $node) {
            if ($node->name->value !== 'categories') {
                continue;
            }
            foreach ($node->selectionSet->selections as $selection) {
                if ($selection->name->value !== 'items') {
                    continue;
                }

                foreach ($selection->selectionSet->selections as $itemSelection) {
                    if ($itemSelection->kind === 'InlineFragment') {
                        foreach ($itemSelection->selectionSet->selections as $inlineSelection) {
                            if ($inlineSelection->kind === 'InlineFragment') {
                                continue;
                            }
                            $fieldNames[] = $this->fieldTranslator->translate($inlineSelection->name->value);
                        }
                        continue;
                    }
                    $fieldNames[] = $this->fieldTranslator->translate($itemSelection->name->value);
                }
            }
        }

        return $fieldNames;
    }
}

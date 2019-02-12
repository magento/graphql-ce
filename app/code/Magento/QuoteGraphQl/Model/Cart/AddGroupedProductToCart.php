<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Model\Quote;

/**
 * Add grouped product to cart
 */
class AddGroupedProductToCart
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param ArrayManager $arrayManager
     * @param DataObjectFactory $dataObjectFactory
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ArrayManager $arrayManager,
        DataObjectFactory $dataObjectFactory,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->arrayManager = $arrayManager;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Add simple product to cart
     *
     * @param Quote $cart
     * @param array $cartItemData
     * @return void
     * @throws GraphQlNoSuchEntityException
     * @throws GraphQlInputException
     */
    public function execute(Quote $cart, array $cartItemData): void
    {
        $sku = $this->extractSku($cartItemData);
        $subProducts = $this->extractSubProducts($cartItemData);

        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__('Could not find a product with SKU "%sku"', ['sku' => $sku]));
        }

        $result = $cart->addProduct($product, $this->createBuyRequest($product, $subProducts));

        if (is_string($result)) {
            throw new GraphQlInputException(__($result));
        }
    }

    /**
     * Extract SKU from cart item data
     *
     * @param array $cartItemData
     * @return string
     * @throws GraphQlInputException
     */
    private function extractSku(array $cartItemData): string
    {
        $sku = $this->arrayManager->get('data/sku', $cartItemData);
        if (!isset($sku)) {
            throw new GraphQlInputException(__('Missing key "sku" in cart item data'));
        }
        return (string)$sku;
    }

    /**
     * Extract Customizable Options from cart item data
     *
     * @param array $cartItemData
     * @return array
     */
    private function extractSubProducts(array $cartItemData): array
    {
        $subProducts = $cartItemData['data']['grouped_products'];

        $subProductsData = [];
        foreach ($subProducts as $subProduct) {
            $subProductsData[$subProduct['sku']] = $subProduct['qty'];
        }

        return $subProductsData;
    }

    /**
     * Format GraphQl input data to a shape that buy request has
     *
     * @param ProductInterface $product
     * @param array $subProducts
     * @return DataObject
     */
    private function createBuyRequest(ProductInterface $product, array $subProducts): DataObject
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', array_keys($subProducts) , 'in')
            ->create();

        $products = $this->productRepository->getList($searchCriteria)->getItems();
        $associatedProductIds = $product->getTypeInstance()->getAssociatedProductIds($product);
        $superGroup = [];

        foreach ($associatedProductIds as $associatedProductId) {
            $superGroup[$associatedProductId] = 0;

            if (isset($products[$associatedProductId])) {
                $superGroup[$associatedProductId] = $subProducts[$products[$associatedProductId]->getSku()];
            }
        }

        return $this->dataObjectFactory->create([
            'data' => [
                'super_group' => $superGroup,
            ],
        ]);
    }
}

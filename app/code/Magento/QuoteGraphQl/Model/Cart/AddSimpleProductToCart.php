<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Quote\Model\Quote;

/**
 * Add simple product to cart
 *
 * TODO: should be replaced for different types resolver
 */
class AddSimpleProductToCart
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
     * @param ArrayManager $arrayManager
     * @param DataObjectFactory $dataObjectFactory
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ArrayManager $arrayManager,
        DataObjectFactory $dataObjectFactory,
        ProductRepositoryInterface $productRepository
    ) {
        $this->arrayManager = $arrayManager;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->productRepository = $productRepository;
    }

    /**
     * Add simple product to cart
     *
     * @param Quote $cart
     * @param array $cartItemData
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Quote $cart, array $cartItemData): void
    {
        $sku = $this->extractSku($cartItemData);
        $qty = $this->extractQty($cartItemData);
        $customizableOptions = $this->extractCustomizableOptions($cartItemData);

        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__('Could not find a product with SKU "%sku"', ['sku' => $sku]));
        }

        $result = $cart->addProduct($product, $this->createBuyRequest($qty, $customizableOptions));

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
     * Extract Qty from cart item data
     *
     * @param array $cartItemData
     * @return float
     * @throws GraphQlInputException
     */
    private function extractQty(array $cartItemData): float
    {
        $qty = $this->arrayManager->get('data/qty', $cartItemData);
        if (!isset($qty)) {
            throw new GraphQlInputException(__('Missing key "qty" in cart item data'));
        }
        return (float)$qty;
    }

    /**
     * Extract Customizable Options from cart item data
     *
     * @param array $cartItemData
     * @return array
     * @throws GraphQlInputException
     */
    private function extractCustomizableOptions(array $cartItemData): array
    {
        $customizableOptions = $this->arrayManager->get('customizable_options', $cartItemData, []);
        $customOptionGroup = [];

        foreach ($customizableOptions as $optionItem) {
            if (!isset($optionItem['value'])) {
                throw new GraphQlInputException(__('Value is required for Option Id %1', $optionItem['id']));
            }

            $customOptionGroup[$optionItem['id']][] = $optionItem['value'];
        }

        $customizableOptionsData = [];
        foreach ($customizableOptions as $customizableOption) {
            if (count($customOptionGroup[$customizableOption['id']]) > 1) {
                //Sort needed to prevent add option twice with same options different ways
                //Eg. {id: 1, value: "1"},{id: 1, value: "2"} and {id: 1, value: "2"},{id: 1, value: "1"}
                arsort($customOptionGroup[$customizableOption['id']]);
                $customizableOptionsData[$customizableOption['id']] = $customOptionGroup[$customizableOption['id']];
            } else {
                $customizableOptionsData[$customizableOption['id']] = $customizableOption['value'];
            }
        }
        return $customizableOptionsData;
    }

    /**
     * Format GraphQl input data to a shape that buy request has
     *
     * @param float $qty
     * @param array $customOptions
     * @return DataObject
     */
    private function createBuyRequest(float $qty, array $customOptions): DataObject
    {
        return $this->dataObjectFactory->create([
            'data' => [
                'qty' => $qty,
                'options' => $customOptions,
            ],
        ]);
    }
}

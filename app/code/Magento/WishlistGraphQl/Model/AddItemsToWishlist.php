<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogGraphQl\Model\GetProductOptionFromRequest;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Wishlist\Model\Wishlist;
use Magento\CatalogGraphQl\Model\ProductOption\ProductOptionInterface;
use Magento\QuoteGraphQl\Model\Cart\BuyRequest\BuyRequestBuilder;

/**
 * Adds items to the wishlist if the corresponding products are available
 */
class AddItemsToWishlist
{
    /**
     * @var GetAvailableProductsBySkuList
     */
    private $getAvailableProductsBySkuList;

    /**
     * @var BuyRequestBuilder
     */
    private $buyRequestBuilder;

    /**
     * @var GetProductOptionFromRequest
     */
    private $getProductOptionFromRequest;

    /**
     * @var GetWishlistForCustomer
     */
    private $getWishlistForCustomer;

    /**
     * @param GetWishlistForCustomer $getWishlistForCustomer
     * @param GetProductOptionFromRequest $getProductOptionFromRequest
     * @param GetAvailableProductsBySkuList $getAvailableProductsBySkuList
     * @param BuyRequestBuilder $buyRequestBuilder
     */
    public function __construct(
        GetWishlistForCustomer $getWishlistForCustomer,
        GetProductOptionFromRequest $getProductOptionFromRequest,
        GetAvailableProductsBySkuList $getAvailableProductsBySkuList,
        BuyRequestBuilder $buyRequestBuilder
    ) {
        $this->getWishlistForCustomer = $getWishlistForCustomer;
        $this->getProductOptionFromRequest = $getProductOptionFromRequest;
        $this->getAvailableProductsBySkuList = $getAvailableProductsBySkuList;
        $this->buyRequestBuilder = $buyRequestBuilder;
    }

    /**
     * Adds items to the wishlist if the corresponding products are available
     *
     * @param int $customerId
     * @param array $items
     * @param int $storeId
     * @return Wishlist
     * @throws GraphQlInputException
     */
    public function execute(int $customerId, array $items, int $storeId): Wishlist
    {
        $wishlist = $this->getWishlistForCustomer->execute($customerId);

        $productsList = [];
        foreach ($items as $item) {
            if (isset($item['parent_sku'])) {
                $productsList[$item['parent_sku']] = $item;
            } else {
                $productsList[$item['sku']] = $item;
            }
        }

        $products = $this->getAvailableProductsBySkuList->execute(array_keys($productsList), $storeId);
        if (count($products) === 0) {
            throw new GraphQlInputException(__('Cannot add the specified items to wishlist'));
        }

        $errors = [];

        try {
            /** @var ProductInterface $product */
            foreach ($products as $product) {
                $buyRequest = $this->createBuyRequest($productsList[$product->getSku()]);
                $item = $wishlist->addNewItem($product, $buyRequest);

                /* The system returns string in case of an error */
                if (is_string($item)) {
                    $errors[] = $item;
                }
            }
        } catch (LocalizedException $exception) {
            throw new GraphQlInputException(__($exception->getMessage()), $exception);
        }

        if (count($errors) > 0) {
            throw new GraphQlInputException(__(implode("\n", $errors)));
        }

        return $wishlist;
    }

    /**
     * Creates buy request
     *
     * @param array $item
     * @return DataObject
     * @throws GraphQlInputException
     */
    private function createBuyRequest(array $item): DataObject
    {
        $cartItemData['data'] = [
            'quantity' => $item['quantity'] ?? 1,
            'sku' => $item['sku']
        ];

        if (isset($item['parent_sku'])) {
            $cartItemData['parent_sku'] = $item['parent_sku'];
        }

        $cartItemData = array_merge_recursive(
            $cartItemData,
            $this->processEnteredOptions($item),
            $this->processSelectedOptions($item)
        );

        return $this->buyRequestBuilder->build($cartItemData);
    }

    /**
     * Extracts entered options for item
     *
     * @param array $item
     * @return array
     * @throws GraphQlInputException
     */
    private function processEnteredOptions(array $item): array
    {
        $enteredOptions = [];

        if (!isset($item['entered_options'])) {
            return $enteredOptions;
        }

        foreach ($item['entered_options'] as $enteredOption) {
            $option = $this->getProductOptionFromRequest->execute(
                $enteredOption,
                ProductOptionInterface::SOURCE_ENTERED
            );

            if ($option->getType() === ProductOptionInterface::TYPE_CUSTOM) {
                $enteredOptions['customizable_options'][] = [
                    'id' => $option->getId(),
                    'value_string' => $option->getValue()
                ];
            } else {
                throw new GraphQlInputException(__("Invalid option type '{$option->getType()}'"));
            }
        }

        return $enteredOptions;
    }

    /**
     * Extracts selected options for item
     *
     * @param array $item
     * @return array
     * @throws GraphQlInputException
     */
    private function processSelectedOptions(array $item): array
    {
        $selectedOptions = [];

        if (!isset($item['selected_options'])) {
            return $selectedOptions;
        }

        foreach ($item['selected_options'] as $selectedOption) {
            $option = $this->getProductOptionFromRequest->execute(
                ['value' => $selectedOption],
                ProductOptionInterface::SOURCE_SELECTED
            );
            if ($option->getType() === ProductOptionInterface::TYPE_CUSTOM) {
                $selectedOptions['customizable_options'][] = [
                    'id' => $option->getId(),
                    'value_string' => $option->getValue()
                ];
            } elseif ($option->getType() === ProductOptionInterface::TYPE_CONFIGURABLE) {
                $selectedOptions['configurable_attributes'][] = [
                    'id' => $option->getId(),
                    'value' => $option->getValue()
                ];
            } else {
                throw new GraphQlInputException(__("Invalid option type '{$option->getType()}'"));
            }
        }

        return $selectedOptions;
    }
}

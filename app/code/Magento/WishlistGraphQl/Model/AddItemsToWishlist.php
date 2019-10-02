<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogGraphQl\Model\GetProductOptionFromRequest;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
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
     * @var BuyRequestBuilder
     */
    private $buyRequestBuilder;

    /**
     * @var GetProductOptionFromRequest
     */
    private $getProductOptionFromRequest;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var GetWishlistForCustomer
     */
    private $getWishlistForCustomer;

    /**
     * @param GetWishlistForCustomer $getWishlistForCustomer
     * @param ProductCollectionFactory $productCollectionFactory
     * @param DataObjectFactory $dataObjectFactory
     * @param GetProductOptionFromRequest $getProductOptionFromRequest
     * @param BuyRequestBuilder $buyRequestBuilder
     */
    public function __construct(
        GetWishlistForCustomer $getWishlistForCustomer,
        ProductCollectionFactory $productCollectionFactory,
        DataObjectFactory $dataObjectFactory,
        GetProductOptionFromRequest $getProductOptionFromRequest,
        BuyRequestBuilder $buyRequestBuilder
    ) {
        $this->getWishlistForCustomer = $getWishlistForCustomer;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->getProductOptionFromRequest = $getProductOptionFromRequest;
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
            $productsList[$item['sku']] = $item;
        }

        $products = $this->getAvailableProductsBySku(array_keys($productsList), $storeId);
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
     * Returns available products for current store according to the SKU list
     *
     * @param array $skuList
     * @param int $storeId
     * @return array
     */
    private function getAvailableProductsBySku(array $skuList, int $storeId): array // TODO: may be decoupled
    {
        return $this->productCollectionFactory->create()
            ->addAttributeToFilter('sku', ['in' => $skuList])
            ->setStoreId($storeId)
            ->setVisibility(
                [Visibility::VISIBILITY_BOTH, Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_IN_SEARCH]
            )
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->getItems();
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
            $cartItemData['data']['parent_sku'] = $item['parent_sku'];
        }

        $cartItemData = array_merge_recursive(
            $cartItemData,
            $this->processEnteredOptions($item),
            $this->processSelectedOptions($item)
        );

        return $this->buyRequestBuilder->build($cartItemData);
    }

    private function processEnteredOptions(array $item): array
    {
        $enteredOptions = [];

        if (!isset($item['entered_options'])) {
            return $enteredOptions;
        }

        foreach ($item['entered_options'] as $enteredOption) {
            $option = $this->getProductOptionFromRequest->execute($enteredOption, ProductOptionInterface::SOURCE_ENTERED);

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

    private function processSelectedOptions(array $item): array
    {
        $selectedOptions = [];

        if (!isset($item['selected_options'])) {
            return $selectedOptions;
        }

        foreach ($item['selected_options'] as $selectedOption) {
            $option = $this->getProductOptionFromRequest->execute(['value' => $selectedOption], ProductOptionInterface::SOURCE_SELECTED);
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

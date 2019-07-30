<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Cart;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\BuyRequest\BuyRequestBuilder;

/**
 * Add simple product to cart
 */
class AddSimpleProductToCart
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var BuyRequestBuilder
     */
    private $buyRequestBuilder;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param BuyRequestBuilder $buyRequestBuilder
     * @param Visibility $visibility
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        BuyRequestBuilder $buyRequestBuilder,
        Visibility $visibility
    ) {
        $this->productRepository = $productRepository;
        $this->buyRequestBuilder = $buyRequestBuilder;
        $this->visibility = $visibility;
    }

    /**
     * Add simple product to cart
     *
     * @param Quote $cart
     * @param array $cartItemData
     * @return void
     * @throws GraphQlNoSuchEntityException
     * @throws GraphQlInputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Quote $cart, array $cartItemData): void
    {
        $sku = $this->extractSku($cartItemData);

        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__('Could not find a product with SKU "%sku"', ['sku' => $sku]));
        }

        if (!in_array(
            (int) $product->getVisibility(),
            $this->visibility->getVisibleInSiteIds(),
            true
        )) {
            throw new GraphQlNoSuchEntityException(__('Could not find a product with SKU "%sku"', ['sku' => $sku]));
        }

        try {
            $result = $cart->addProduct($product, $this->buyRequestBuilder->build($cartItemData));
        } catch (\Exception $e) {
            throw new GraphQlInputException(
                __(
                    'Could not add the product with SKU %sku to the shopping cart: %message',
                    ['sku' => $sku, 'message' => $e->getMessage()]
                )
            );
        }

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
        if (!isset($cartItemData['data']['sku']) || empty($cartItemData['data']['sku'])) {
            throw new GraphQlInputException(__('Missed "sku" in cart item data'));
        }
        return (string)$cartItemData['data']['sku'];
    }
}

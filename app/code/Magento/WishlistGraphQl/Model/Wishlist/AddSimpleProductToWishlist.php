<?php

namespace Magento\WishlistGraphQl\Model\Wishlist;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\QuoteGraphQl\Model\Cart\BuyRequest\BuyRequestBuilder;
use Magento\Wishlist\Model\Wishlist;

class AddSimpleProductToWishlist
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
     * @param ProductRepositoryInterface $productRepository
     * @param BuyRequestBuilder $buyRequestBuilder
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        BuyRequestBuilder $buyRequestBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->buyRequestBuilder = $buyRequestBuilder;
    }

    /**
     * Add simple product to cart
     *
     * @param Wishlist $wishlist
     * @param array $wishlistItemData
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    public function execute(Wishlist $wishlist, array $wishlistItemData): void
    {
        $sku = $this->extractSku($wishlistItemData);

        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__('Could not find a product with SKU "%sku"', ['sku' => $sku]));
        }

        try {
            $item = $wishlist->addNewItem($product, $this->buyRequestBuilder->build($wishlistItemData));
        } catch (Exception $e) {
            throw new GraphQlInputException(
                __(
                    'Could not add the product with SKU %sku to the wishlist: %message',
                    ['sku' => $sku, 'message' => $e->getMessage()]
                )
            );
        }

        if ($item->getHasError()) {
            throw new GraphQlInputException(
                __(
                    'Could not add the product with SKU %sku to the wishlist: %message',
                    ['sku' => $sku, 'message' => $item->getMessage()]
                )
            );
        }
        if (is_string($item)) {
            throw new GraphQlInputException(__($item));
        }
    }

    /**
     * Extract SKU from cart item data
     *
     * @param array $wishlistItemData
     * @return string
     * @throws GraphQlInputException
     */
    private function extractSku(array $wishlistItemData): string
    {
        // Need to keep this for configurable product and backward compatibility.
        if (!empty($wishlistItemData['parent_sku'])) {
            return (string)$wishlistItemData['parent_sku'];
        }
        if (empty($wishlistItemData['data']['sku'])) {
            throw new GraphQlInputException(__('Missed "sku" in cart item data'));
        }
        return (string)$wishlistItemData['data']['sku'];
    }
}

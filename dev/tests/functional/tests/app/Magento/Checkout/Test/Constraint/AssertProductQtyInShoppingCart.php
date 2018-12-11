<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Checkout\Test\Constraint;

use Magento\Catalog\Test\Fixture\CatalogProductSimple;
use Magento\Checkout\Test\Constraint\Utils\CartPageLoadTrait;
use Magento\Checkout\Test\Fixture\Cart;
use Magento\Checkout\Test\Fixture\Cart\Items;
use Magento\Checkout\Test\Page\CheckoutCart;
use Magento\Mtf\Constraint\AbstractAssertForm;
use Magento\Mtf\Fixture\FixtureInterface;

/**
 * Class AssertProductQtyInShoppingCart
 * Assert that quantity in the shopping cart is equals to expected quantity from data set
 */
class AssertProductQtyInShoppingCart extends AbstractAssertForm
{
    use CartPageLoadTrait;

    /**
     * Assert that quantity in the shopping cart is equals to expected quantity from data set
     *
     * @param CheckoutCart $checkoutCart
     * @param Cart $cart
     * @return void
     */
    public function processAssert(CheckoutCart $checkoutCart, Cart $cart)
    {
        $checkoutCart->open();
        $this->waitForCartPageLoaded($checkoutCart);

        /** @var Items $sourceProducts */
        $sourceProducts = $cart->getDataFieldConfig('items')['source'];
        $products = $sourceProducts->getProducts();
        $items = $cart->getItems();
        $productsData = [];
        $cartData = [];

        foreach ($items as $key => $item) {
            /** @var CatalogProductSimple $product */
            $product = $products[$key];
            $productName = $product->getName();
            /** @var FixtureInterface $item */
            $checkoutItem = $item->getData();
            $cartItem = $checkoutCart->getCartBlock()->getCartItem($product);

            $productsData[$productName] = [
                'qty' => $checkoutItem['qty'],
            ];
            $cartData[$productName] = [
                'qty' => $cartItem->getQty(),
            ];
        }

        $error = $this->verifyData($productsData, $cartData, true);
        \PHPUnit\Framework\Assert::assertEmpty($error, $error);
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        return 'Quantity in the shopping cart equals to expected quantity from data set.';
    }
}

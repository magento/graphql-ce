<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\Resolver\Product;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Pricing\Price\TierPriceFactory as PriceFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\TierPrice;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;


/**
 * @inheritdoc
 *
 * Format a product's tier price information to conform to GraphQL schema representation
 */
class TierPrices implements ResolverInterface
{
    /**
     * @var PriceFactory
     */
    private $priceFactory;

    /**
     * @param PriceFactory $priceFactory
     */
    public function __construct(
        PriceFactory $priceFactory
    ) {
        $this->priceFactory = $priceFactory;
    }

    /**
     * @inheritdoc
     *
     * Format product's tier price data to conform to GraphQL schema
     *
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @throws \Exception
     * @return null|array
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var Product $product */
        $product = $value['model'];

        $tierPrices = $this->priceFactory->create([
            'saleableItem' => $product,
            'quantity' => 1,
        ])->getTierPriceList();

        $output = [];
        foreach ($tierPrices as $tierPrice) {
            $output[] = [
                'customer_group_id' => $tierPrice['cust_group'],
                'qty' => $tierPrice['price_qty'],
                'value' => $tierPrice['website_price'],
                'percentage_value' => $tierPrice['percentage_value'],
                'website_id' => $tierPrice['website_id'],
            ];
        }

        return $output;
    }
}

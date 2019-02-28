<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\ExtractDataFromAddress;

/**
 * @inheritdoc
 */
class ShippingAddresses implements ResolverInterface
{
    /**
     * @var ExtractDataFromAddress
     */
    private $extractDataFromAddress;

    /**
     * @param ExtractDataFromAddress $extractDataFromAddress
     */
    public function __construct(ExtractDataFromAddress $extractDataFromAddress)
    {
        $this->extractDataFromAddress = $extractDataFromAddress;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        $cart = $value['model'];

        $addressesData = [];
        $shippingAddresses = $cart->getAllShippingAddresses();

        if (count($shippingAddresses)) {
            foreach ($shippingAddresses as $shippingAddress) {
                $addressesData[] = $this->extractDataFromAddress->execute($shippingAddress);
            }
        }
        return $addressesData;
    }
}

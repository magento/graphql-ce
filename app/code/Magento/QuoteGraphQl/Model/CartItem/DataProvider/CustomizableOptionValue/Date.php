<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\CartItem\DataProvider\CustomizableOptionValue;

use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\Product\Option\Type\Date as DateOptionType;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option as SelectedOption;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\CustomizableOptionValueInterface;

/**
 * @inheritdoc
 */
class Date implements CustomizableOptionValueInterface
{
    /**
     * @var PriceUnitLabel
     */
    private $priceUnitLabel;

    /**
     * @param PriceUnitLabel $priceUnitLabel
     */
    public function __construct(PriceUnitLabel $priceUnitLabel)
    {
        $this->priceUnitLabel = $priceUnitLabel;
    }

    /**
     * @inheritdoc
     */
    public function getData(QuoteItem $cartItem, Option $option, SelectedOption $selectedOption): array
    {
        /** @var DateOptionType $optionTypeRenderer */
        $optionTypeRenderer = $option->groupFactory($option->getType())
            ->setOption($option)
            ->setConfigurationItemOption($selectedOption);

        $selectedOptionId = $selectedOption->getId();
        $selectedValue = $selectedOption->getValue();
        $priceType = $option->getPriceType();
        $priceValueUnits = $this->priceUnitLabel->getData($priceType);
        $price = $option->getPrice(true);
        $selectedOptionValueData = [
            'id' => $selectedOptionId,
            'label' => $option->getTitle(),
            'value' => $optionTypeRenderer->getFormattedOptionValue($selectedValue),
            'price' => [
                'type' => strtoupper($priceType),
                'units' => $priceValueUnits,
                'value' => $price,
            ],
        ];

        return [$selectedOptionValueData];
    }
}

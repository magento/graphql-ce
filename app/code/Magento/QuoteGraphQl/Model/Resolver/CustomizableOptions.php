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
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\CustomizableOption;

/**
 * @inheritdoc
 */
class CustomizableOptions implements ResolverInterface
{
    /**
     * @var CustomizableOption
     */
    private $customizableOption;

    /**
     * @param CustomizableOption $customizableOption
     */
    public function __construct(
        CustomizableOption $customizableOption
    ) {
        $this->customizableOption = $customizableOption;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var QuoteItem $cartItem */
        $cartItem = $value['model'];
        $quoteItemOption = $cartItem->getOptionByCode('option_ids');

        if (null === $quoteItemOption) {
            return [];
        }

        $customizableOptionsData = [];
        $customizableOptionIds = explode(',', $quoteItemOption->getValue());

        foreach ($customizableOptionIds as $customizableOptionId) {
            $customizableOption = $this->customizableOption->getData(
                $cartItem,
                (int)$customizableOptionId
            );

            if ('date_time' === $customizableOption['type']) {
                $this->setOriginalDateTime($cartItem, $customizableOption, $customizableOptionId);
            }

            $customizableOptionsData[] = $customizableOption;
        }
        return $customizableOptionsData;
    }

    public function setOriginalDateTime(QuoteItem $cartItem, &$customizableOption, $customizableOptionId)
    {
        $originalOption = $cartItem->getOptionsByCode();

        if (is_array($originalOption)
            && is_string($customizableOptionId)
            && array_key_exists('option_' . $customizableOptionId, $originalOption)
        ) {
            $originalValue = $originalOption['option_' . $customizableOptionId]->getOrigData()['value'];

            if ('' !== $originalValue) {
                $customizableOption['values'][0]['value'] = $originalValue;
            }
        }
    }
}

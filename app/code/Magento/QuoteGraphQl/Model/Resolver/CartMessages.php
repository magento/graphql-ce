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
use Magento\Quote\Model\Quote;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;

/**
 * @inheritdoc
 */
class CartMessages implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        $cart = $value['model'];
        if (empty($cart->getData('has_error'))) {
            return [];
        }
        return $this->getCartErrors($cart);
    }

    /**
     * Collecting cart errors
     *
     * @param Quote $cart
     * @return array
     */
    private function getCartErrors(Quote $cart): array
    {
        $errorMessages = [];

        /** @var AbstractMessage $error */
        foreach ($cart->getMessages() as $idettifier => $error) {
            $errorMessages[] = [
                'identifier' => $idettifier,
                'text' => $error->getText()
            ];
        }

        return $errorMessages;
    }
}

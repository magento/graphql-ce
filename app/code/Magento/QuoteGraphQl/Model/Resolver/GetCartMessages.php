<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Framework\Message\AbstractMessage;
use Magento\Quote\Model\Quote;

/**
 * @inheritdoc
 */
class GetCartMessages implements ResolverInterface
{
    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @param GetCartForUser $getCartForUser
     */
    public function __construct(
        GetCartForUser $getCartForUser
    ) {
        $this->getCartForUser = $getCartForUser;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $cartId = $args['input']['cart_id'];
        if (isset($cartId) === false || empty($cartId)) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing.'));
        }
        $currentUserId = $context->getUserId();
        $cart = $this->getCartForUser->execute($cartId, $currentUserId);
        if (empty($cart->getData('has_error'))) {
            throw new GraphQlNoSuchEntityException(__('Requested cart hasn\'t errors.'));
        }
        return ['messages' => $this->getCartErrors($cart)];
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
        foreach ($cart->getErrors() as $error) {
            $errorMessages[] = $error->getText();
        }

        return $errorMessages;
    }
}

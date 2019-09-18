<?php

declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\Data\CartInterface;
use Magento\QuoteGraphQl\Model\Cart\GetAllCustomerCarts;

class CustomerCarts implements ResolverInterface
{
    /**
     * @var GetAllCustomerCarts
     */
    private $customerCarts;

    public function __construct(
        GetAllCustomerCarts $customerCarts
    ) {
        $this->customerCarts = $customerCarts;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $currentUserId = $context->getUserId();
        if (!$currentUserId) {
            throw new GraphQlAuthorizationException(__('Unauthorized customers cannot perform this operation'));
        }

        $carts = $this->customerCarts->execute($currentUserId);
        $result = [];
        /** @var CartInterface $cart */
        foreach ($carts as $cart) {
            $result[] = [
                'model' => $cart
            ];
        }
        return $result;
    }
}

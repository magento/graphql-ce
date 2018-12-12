<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\CustomerGraphQl\Model\Customer\CheckCustomerAccount;

/**
 * CurrentSessionLastOrder data resolver
 */
class CurrentSessionLastOrder implements ResolverInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * CurrentSessionLastOrder constructor.
     *
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @return mixed
     */
    private function getLastRealOrderId()
    {
        return $this->checkoutSession->getLastRealOrderId();
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if ($this->getLastRealOrderId()) {
            $order = $this->orderFactory->create()->loadByIncrementId($this->getLastRealOrderId());
            return [
                'id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'created_at' => $order->getCreatedAt(),
                'grand_total' => $order->getGrandTotal(),
                'status' => $order->getStatus(),
            ];
            return $order;
        }
        return [];
    }
}

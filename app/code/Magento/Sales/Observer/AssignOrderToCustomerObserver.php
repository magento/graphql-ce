<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Sales\Observer;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Assign order to customer created after issuing guest order.
 */
class AssignOrderToCustomerObserver implements ObserverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        /** @var CustomerInterface $customer */
        $customer = $event->getData('customer_data_object');
        /** @var array $delegateData */
        $delegateData = $event->getData('delegate_data');
        if (array_key_exists('__sales_assign_order_id', $delegateData)) {
            $orderId = $delegateData['__sales_assign_order_id'];
            $order = $this->orderRepository->get($orderId);
            if (!$order->getCustomerId()) {
                //assign customer info to order after customer creation.
                $order->setCustomerId($customer->getId())
                    ->setCustomerIsGuest(0)
                    ->setCustomerEmail($customer->getEmail())
                    ->setCustomerFirstname($customer->getFirstname())
                    ->setCustomerLastname($customer->getLastname())
                    ->setCustomerMiddlename($customer->getMiddlename())
                    ->setCustomerPrefix($customer->getPrefix())
                    ->setCustomerSuffix($customer->getSuffix())
                    ->setCustomerGroupId($customer->getGroupId());
                $this->orderRepository->save($order);
            }
        }
    }
}

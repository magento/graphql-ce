<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;

/**
 * Orders data reslover
 */
class Orders implements ResolverInterface
{
    /**
     * @var CollectionFactoryInterface
     */
    private $collectionFactory;

    /**
     * @param CollectionFactoryInterface $collectionFactory
     */
    public function __construct(
        CollectionFactoryInterface $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
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
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        $items = [];
        $orders = $this->collectionFactory->create($context->getUserId());

        $fieldsSelection = $info->getFieldSelection(2);
        $fields = $fieldsSelection['items'];

        if (isset($args['id']) && $args['id']) {
            $orders->addFieldToFilter($orders->getIdFieldName(), $args['id']);
        }

        if (isset($args['status']) && $args['status']) {
            $orders->addFieldToFilter('status', $args['status']);
        }

        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orders as $order) {
            $items[] = [
                'id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'created_at' => $order->getCreatedAt(),
                'grand_total' => $order->getGrandTotal(),
                'status' => $order->getStatus(),
                'shipping_description' => $order->getShippingDescription(),
                'shipping_amount' => $order->getShippingAmount(),
                'discount_amount' => $order->getDiscountAmount(),
                'tax_amount' => $order->getTaxAmount(),
                'sub_total' => $order->getSubtotal()
            ];

            if (isset($fields['items'])) {
                $item['items'] = $order->getAllVisibleItems();
            }

            if (isset($fields['shipping_address'])) {
                $shippingAddress = ($order->getShippingAddress()) ? $order->getShippingAddress() : $order->getBillingAddress();
                $item['shipping_address'] = $this->formatAddressData($shippingAddress);
            }

            if (isset($fields['billing_address'])) {
                $item['billing_address'] = $this->formatAddressData($order->getBillingAddress());
            }

            if (isset($fields['payment_method_title'])) {
                $item['payment_method_title'] = $order->getPayment()->getMethodInstance()->getTitle();
            }

            $items[] = $item;
        }
        return ['items' => $items];
    }

    /**
     * @param $address
     * @return mixed
     */
    public function formatAddressData($address)
    {
        $addressData = $address->getData();
        $addressData['street'] = $address->getStreet();
        return $addressData;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Sales\Service\V1;

use Magento\TestFramework\TestCase\WebapiAbstract;

class OrderGetTest extends WebapiAbstract
{
    const RESOURCE_PATH = '/V1/orders';

    const SERVICE_READ_NAME = 'salesOrderRepositoryV1';

    const SERVICE_VERSION = 'V1';

    const ORDER_INCREMENT_ID = '100000001';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * @magentoApiDataFixture Magento/Sales/_files/order.php
     */
    public function testOrderGet()
    {
        $expectedOrderData = [
            'base_subtotal' => '100.0000',
            'subtotal' => '100.0000',
            'customer_is_guest' => '1',
            'increment_id' => self::ORDER_INCREMENT_ID,
        ];
        $expectedPayments = [
            'method' => 'checkmo',
            'additional_information' => [
                0 => '11122', // last transaction id
                // metadata
                1 => json_encode([
                    'type' => 'free',
                    'fraudulent' => false
                ])
            ]
        ];
        $expectedBillingAddressNotEmpty = [
            'city',
            'postcode',
            'lastname',
            'street',
            'region',
            'telephone',
            'country_id',
            'firstname',
        ];
        $expectedShippingAddress = [
            'address_type' => 'shipping',
            'city' => 'Los Angeles',
            'email' => 'customer@null.com',
            'postcode' => '11111',
            'region' => 'CA'
        ];

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->objectManager->create(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId(self::ORDER_INCREMENT_ID);

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $order->getId(),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'get',
            ],
        ];
        $result = $this->_webApiCall($serviceInfo, ['id' => $order->getId()]);

        foreach ($expectedOrderData as $field => $value) {
            $this->assertArrayHasKey($field, $result);
            $this->assertEquals($value, $result[$field]);
        }

        $this->assertArrayHasKey('payment', $result);
        foreach ($expectedPayments as $field => $value) {
            $this->assertEquals($value, $result['payment'][$field]);
        }

        $this->assertArrayHasKey('billing_address', $result);
        foreach ($expectedBillingAddressNotEmpty as $field) {
            $this->assertArrayHasKey($field, $result['billing_address']);
        }

        self::assertArrayHasKey('extension_attributes', $result);
        self::assertArrayHasKey('shipping_assignments', $result['extension_attributes']);

        $shippingAssignments = $result['extension_attributes']['shipping_assignments'];
        self::assertCount(1, $shippingAssignments);
        $shippingAddress = $shippingAssignments[0]['shipping']['address'];
        foreach ($expectedShippingAddress as $key => $value) {
            self::assertArrayHasKey($key, $shippingAddress);
            self::assertEquals($value, $shippingAddress[$key]);
        }

        //check that nullable fields were marked as optional and were not sent
        foreach ($result as $value) {
            $this->assertNotNull($value);
        }
    }

    /**
     * @magentoApiDataFixture Magento/Sales/_files/order_with_tax.php
     */
    public function testOrderGetExtensionAttributes()
    {
        $expectedTax = [
            'code' => 'US-NY-*-Rate 1',
            'type' => 'shipping'
        ];

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->objectManager->create(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId(self::ORDER_INCREMENT_ID);

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/' . $order->getId(),
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => self::SERVICE_READ_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_READ_NAME . 'get',
            ],
        ];
        $result = $this->_webApiCall($serviceInfo, ['id' => $order->getId()]);

        $appliedTaxes = $result['extension_attributes']['applied_taxes'];
        $this->assertEquals($expectedTax['code'], $appliedTaxes[0]['code']);
        $appliedTaxes = $result['extension_attributes']['item_applied_taxes'];
        $this->assertEquals($expectedTax['type'], $appliedTaxes[0]['type']);
        $this->assertNotEmpty($appliedTaxes[0]['applied_taxes']);
        $this->assertEquals(true, $result['extension_attributes']['converting_from_quote']);
    }
}

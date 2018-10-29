<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

include "customer.php";

use Magento\Customer\Model\Customer;
use Magento\Vault\Model\PaymentToken;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$paymentTokens = [
    [
        'customer_id' => 1,
        'public_hash' => 'H123456789',
        'payment_method_code' => 'code_first',
        'gateway_token' => 'ABC1234',
        'type' => 'card',
        'expires_at' => strtotime('+1 year'),
        'details' => '{"type":"VI","maskedCC":"9876","expirationDate":"12\/2020"}',
        'is_active' => 1
    ],
    [
        'customer_id' => 1,
        'public_hash' => 'H987654321',
        'payment_method_code' => 'code_second',
        'gateway_token' => 'ABC4567',
        'type' => 'card',
        'expires_at' => strtotime('+1 year'),
        'details' => '{"type":"MC","maskedCC":"4444","expirationDate":"12\/2030"}',
        'is_active' => 1
    ],
    [
        'customer_id' => 1,
        'public_hash' => 'H1122334455',
        'payment_method_code' => 'code_third',
        'gateway_token' => 'ABC7890',
        'type' => 'account',
        'expires_at' => strtotime('+1 year'),
        'details' => '{"type":"DI","maskedCC":"0001","expirationDate":"12\/2040"}',
        'is_active' => 1
    ],
    [
        'customer_id' => 1,
        'public_hash' => 'H5544332211',
        'payment_method_code' => 'code_fourth',
        'gateway_token' => 'ABC0000',
        'type' => 'account',
        'expires_at' => strtotime('+1 year'),
        'details' => '',
        'is_active' => 0
    ],
];
/** @var array $tokenData */
foreach ($paymentTokens as $tokenData) {
    /** @var PaymentToken $bookmark */
    $paymentToken = $objectManager->create(PaymentToken::class);
    $paymentToken
        ->setData($tokenData)
        ->save();
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Customer;

use Magento\Customer\Model\CustomerAuthUpdate;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Tests for update customer v2
 */
class UpdateCustomerV2Test extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var CustomerAuthUpdate
     */
    private $customerAuthUpdate;

    /**
     * @var LockCustomer
     */
    private $lockCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
        $this->customerAuthUpdate = Bootstrap::getObjectManager()->get(CustomerAuthUpdate::class);
        $this->lockCustomer = Bootstrap::getObjectManager()->get(LockCustomer::class);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testUpdateCustomer()
    {
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';

        $newPrefix = 'Dr';
        $newFirstname = 'Richard';
        $newMiddlename = 'Riley';
        $newLastname = 'Rowe';
        $newSuffix = 'III';
        $newDob = '3/11/1972';
        $newTaxVat = 'GQL1234567';
        $newGender = 2;

        $query = <<<QUERY
mutation {
    updateCustomerV2(
        input: {
            prefix: "{$newPrefix}"
            firstname: "{$newFirstname}"
            middlename: "{$newMiddlename}"
            lastname: "{$newLastname}"
            suffix: "{$newSuffix}"
            date_of_birth: "{$newDob}"
            taxvat: "{$newTaxVat}"
            gender: {$newGender}
        }
    ) {
        customer {
            prefix
            firstname
            middlename
            lastname
            suffix
            date_of_birth
            taxvat
            email
            gender
        }
    }
}
QUERY;
        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $this->getCustomerAuthHeaders($currentEmail, $currentPassword)
        );

        $this->assertEquals($newPrefix, $response['updateCustomerV2']['customer']['prefix']);
        $this->assertEquals($newFirstname, $response['updateCustomerV2']['customer']['firstname']);
        $this->assertEquals($newMiddlename, $response['updateCustomerV2']['customer']['middlename']);
        $this->assertEquals($newLastname, $response['updateCustomerV2']['customer']['lastname']);
        $this->assertEquals($newSuffix, $response['updateCustomerV2']['customer']['suffix']);
        $this->assertEquals($newDob, $response['updateCustomerV2']['customer']['date_of_birth']);
        $this->assertEquals($newTaxVat, $response['updateCustomerV2']['customer']['taxvat']);
        $this->assertEquals($newGender, $response['updateCustomerV2']['customer']['gender']);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @expectedException \Exception
     * @expectedExceptionMessage "input" value should be specified
     */
    public function testUpdateCustomerIfInputDataIsEmpty()
    {
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';

        $query = <<<QUERY
mutation {
    updateCustomerV2(
        input: {

        }
    ) {
        customer {
            firstname
        }
    }
}
QUERY;
        $this->graphQlMutation($query, [], '', $this->getCustomerAuthHeaders($currentEmail, $currentPassword));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The current customer isn't authorized.
     */
    public function testUpdateCustomerIfUserIsNotAuthorized()
    {
        $newFirstname = 'Richard';

        $query = <<<QUERY
mutation {
    updateCustomerV2(
        input: {
            firstname: "{$newFirstname}"
        }
    ) {
        customer {
            firstname
        }
    }
}
QUERY;
        $this->graphQlMutation($query);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @expectedException \Exception
     * @expectedExceptionMessage The account is locked.
     */
    public function testUpdateCustomerIfAccountIsLocked()
    {
        $this->lockCustomer->execute(1);

        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';
        $newFirstname = 'Richard';

        $query = <<<QUERY
mutation {
    updateCustomerV2(
        input: {
            firstname: "{$newFirstname}"
        }
    ) {
        customer {
            firstname
        }
    }
}
QUERY;
        $this->graphQlMutation($query, [], '', $this->getCustomerAuthHeaders($currentEmail, $currentPassword));
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @expectedException \Exception
     * @expectedExceptionMessage Required parameters are missing: First Name
     */
    public function testEmptyCustomerName()
    {
        $currentEmail = 'customer@example.com';
        $currentPassword = 'password';

        $query = <<<QUERY
mutation {
    updateCustomerV2(
        input: {
            firstname: ""
        }
    ) {
        customer {
            email
        }
    }
}
QUERY;
        $this->graphQlMutation($query, [], '', $this->getCustomerAuthHeaders($currentEmail, $currentPassword));
    }

    /**
     * @param string $email
     * @param string $password
     * @return array
     */
    private function getCustomerAuthHeaders(string $email, string $password): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($email, $password);
        return ['Authorization' => 'Bearer ' . $customerToken];
    }
}

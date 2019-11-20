<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Customer;

use Magento\Customer\Model\CustomerAuthUpdate;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test update customer email
 */
class UpdateCustomerEmailTest extends GraphQlAbstract
{
    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var CustomerAuthUpdate
     */
    private $customerAuthUpdate;

    protected function setUp(): void
    {
        $this->customerTokenService = Bootstrap::getObjectManager()->get(CustomerTokenServiceInterface::class);
        $this->customerRegistry = Bootstrap::getObjectManager()->get(CustomerRegistry::class);
        $this->customerAuthUpdate = Bootstrap::getObjectManager()->get(CustomerAuthUpdate::class);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     */
    public function testUpdateEmail()
    {
        $currentEmail = 'customer@example.com';
        $newEmail = 'changed-email@example.com';
        $currentPassword = 'password';

        $query = $this->getQuery($newEmail, $currentPassword);
        $headerMap = $this->getCustomerAuthHeaders($currentEmail, $currentPassword);

        $response = $this->graphQlMutation($query, [], '', $headerMap);
        $this->assertEquals($newEmail, $response['updateCustomerEmail']['customer']['email']);
        $this->assertEquals('John', $response['updateCustomerEmail']['customer']['firstname']);
        $this->assertEquals('Smith', $response['updateCustomerEmail']['customer']['lastname']);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The current customer isn't authorized.
     */
    public function testUpdateEmailIfUserIsNotAuthorizedTest()
    {
        $query = $this->getQuery('currentpassword', 'newpassword');
        $this->graphQlMutation($query);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @expectedException \Exception
     * @expectedExceptionMessage Invalid credentials
     */
    public function testUpdateEmailIfPasswordIsInvalid()
    {
        $currentEmail = 'customer@example.com';
        $newEmail = 'changed-email@example.com';
        $currentPassword = 'password';
        $incorrectCurrentPassword = 'password-incorrect';

        $query = $this->getQuery($newEmail, $incorrectCurrentPassword);

        $headerMap = $this->getCustomerAuthHeaders($currentEmail, $currentPassword);
        $this->graphQlMutation($query, [], '', $headerMap);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @expectedException \Exception
     * @expectedExceptionMessage Specify the "password" value.
     */
    public function testUpdateEmailIfPasswordIsEmpty()
    {
        $currentEmail = 'customer@example.com';
        $newEmail = 'changed-email@example.com';
        $currentPassword = 'password';
        $incorrectCurrentPassword = '';

        $query = $this->getQuery($newEmail, $incorrectCurrentPassword);

        $headerMap = $this->getCustomerAuthHeaders($currentEmail, $currentPassword);
        $this->graphQlMutation($query, [], '', $headerMap);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @expectedException \Exception
     * @expectedExceptionMessage Specify the "email" value.
     */
    public function testUpdateEmailIfEmailIsEmpty()
    {
        $currentEmail = 'customer@example.com';
        $newEmail = ' ';
        $currentPassword = 'password';

        $query = $this->getQuery($newEmail, $currentPassword);

        $headerMap = $this->getCustomerAuthHeaders($currentEmail, $currentPassword);
        $this->graphQlMutation($query, [], '', $headerMap);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @expectedException \Exception
     * @expectedExceptionMessage The account is locked.
     */
    public function testUpdateEmailIfCustomerIsLocked()
    {
        $currentEmail = 'customer@example.com';
        $newEmail = 'changed-email@example.com';
        $currentPassword = 'password';

        $this->lockCustomer(1);
        $query = $this->getQuery($newEmail, $currentPassword);

        $headerMap = $this->getCustomerAuthHeaders($currentEmail, $currentPassword);
        $this->graphQlMutation($query, [], '', $headerMap);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/two_customers.php
     * @expectedException \Exception
     * @expectedExceptionMessage A customer with the same email address already exists in an associated website
     */
    public function testUpdateEmailIfEmailIsAlreadyInUse()
    {
        $currentEmail = 'customer@example.com';
        $newEmail = 'customer_two@example.com';
        $currentPassword = 'password';

        $query = $this->getQuery($newEmail, $currentPassword);

        $headerMap = $this->getCustomerAuthHeaders($currentEmail, $currentPassword);
        $this->graphQlMutation($query, [], '', $headerMap);
    }

    /**
     * @param int $customerId
     *
     * @return void
     * @throws NoSuchEntityException
     */
    private function lockCustomer(int $customerId): void
    {
        $customerSecure = $this->customerRegistry->retrieveSecureData($customerId);
        $customerSecure->setLockExpires('2030-12-31 00:00:00');
        $this->customerAuthUpdate->saveAuth($customerId);
    }

    /**
     * @param $email
     * @param $password
     * @return string
     */
    private function getQuery($email, $password): string
    {
        $query = <<<QUERY
mutation {
  updateCustomerEmail(
    email: "$email",
    password: "$password"
  ) {
    customer {
      email
      firstname
      lastname
    }
  }
}
QUERY;

        return $query;
    }

    /**
     * @param string $email
     * @param string $password
     *
     * @return array
     * @throws AuthenticationException
     */
    private function getCustomerAuthHeaders(string $email, string $password): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($email, $password);
        return ['Authorization' => 'Bearer ' . $customerToken];
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\User\Model;

use Magento\Framework\Encryption\Encryptor;

/**
 * @magentoAppArea adminhtml
 */
class UserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\User\Model\User
     */
    protected $_model;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Magento\Authorization\Model\Role
     */
    protected static $_newRole;

    /**
     * @var Encryptor
     */
    private $encryptor;

    protected function setUp()
    {
        $this->_model = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\User\Model\User::class
        );
        $this->_dateTime = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Framework\Stdlib\DateTime::class
        );
        $this->encryptor = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            Encryptor::class
        );
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testCRUD()
    {
        $this->_model->setFirstname(
            "John"
        )->setLastname(
            "Doe"
        )->setUsername(
            'user2'
        )->setPassword(
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        )->setEmail(
            'user@magento.com'
        );

        $crud = new \Magento\TestFramework\Entity($this->_model, ['firstname' => '_New_name_']);
        $crud->testCrud();
    }

    /**
     * @magentoDataFixture Magento/User/_files/dummy_user.php
     */
    public function testCreatedOnUpdate()
    {
        $this->_model->loadByUsername('user_created_date');
        $this->assertEquals('2010-01-06 00:00:00', $this->_model->getCreated());
        //reload to update lognum record
        $this->_model->getResource()->recordLogin($this->_model);
        $this->_model->reload();
        $this->assertEquals('2010-01-06 00:00:00', $this->_model->getCreated());
    }

    /**
     * Ensure that an exception is not thrown, if the user does not exist
     */
    public function testLoadByUsername()
    {
        $this->_model->loadByUsername('non_existing_user');
        $this->assertNull($this->_model->getId(), 'The admin user has an unexpected ID');
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $this->assertNotEmpty($this->_model->getId(), 'The admin user should have been loaded');
    }

    /**
     * Test that user role is updated after save
     *
     * @magentoDataFixture roleDataFixture
     */
    public function testUpdateRoleOnSave()
    {
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $this->assertEquals(\Magento\TestFramework\Bootstrap::ADMIN_ROLE_NAME, $this->_model->getRole()->getRoleName());
        $this->_model->setRoleId(self::$_newRole->getId())->save();
        $this->assertEquals('admin_role', $this->_model->getRole()->getRoleName());
    }

    /**
     * phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function roleDataFixture()
    {
        self::$_newRole = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Authorization\Model\Role::class
        );
        self::$_newRole->setName('admin_role')->setRoleType('G')->setPid('1');
        self::$_newRole->save();
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testSaveExtra()
    {
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $this->_model->saveExtra(['test' => 'val']);
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $extra = $this->_model->getExtra();
        $this->assertEquals($extra['test'], 'val');
    }

    /**
     * @magentoDataFixture roleDataFixture
     */
    public function testGetRoles()
    {
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $roles = $this->_model->getRoles();
        $this->assertEquals(1, count($roles));
        $this->assertEquals(\Magento\TestFramework\Bootstrap::ADMIN_ROLE_NAME, $this->_model->getRole()->getRoleName());
        $this->_model->setRoleId(self::$_newRole->getId())->save();
        $roles = $this->_model->getRoles();
        $this->assertEquals(1, count($roles));
        $this->assertEquals(self::$_newRole->getId(), $roles[0]);
    }

    /**
     * @magentoDataFixture roleDataFixture
     */
    public function testGetRole()
    {
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $role = $this->_model->getRole();
        $this->assertInstanceOf(\Magento\Authorization\Model\Role::class, $role);
        $this->assertEquals(\Magento\TestFramework\Bootstrap::ADMIN_ROLE_NAME, $this->_model->getRole()->getRoleName());
        $this->_model->setRoleId(self::$_newRole->getId())->save();
        $role = $this->_model->getRole();
        $this->assertEquals(self::$_newRole->getId(), $role->getId());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testDeleteFromRole()
    {
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $roles = $this->_model->getRoles();
        $this->_model->setRoleId(reset($roles))->deleteFromRole();
        $role = $this->_model->getRole();
        $this->assertNull($role->getId());
    }

    public function testRoleUserExists()
    {
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $role = $this->_model->getRole();
        $this->_model->setRoleId($role->getId());
        $this->assertTrue($this->_model->roleUserExists());
        $this->_model->setRoleId(100);
        $this->assertFalse($this->_model->roleUserExists());
    }

    public function testGetCollection()
    {
        $this->assertInstanceOf(
            \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection::class,
            $this->_model->getCollection()
        );
    }

    public function testGetName()
    {
        $firstname = \Magento\TestFramework\Bootstrap::ADMIN_FIRSTNAME;
        $lastname = \Magento\TestFramework\Bootstrap::ADMIN_LASTNAME;
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $this->assertEquals("$firstname $lastname", $this->_model->getName());
        $this->assertEquals("$firstname///$lastname", $this->_model->getName('///'));
    }

    public function testGetUninitializedAclRole()
    {
        $newuser = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\User\Model\User::class);
        $newuser->setUserId(10);
        $this->assertNull($newuser->getAclRole(), "User role was not initialized and is expected to be empty.");
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAdminConfigFixture admin/captcha/enable 0
     * @magentoAdminConfigFixture admin/security/use_case_sensitive_login 1
     */
    public function testAuthenticate()
    {
        $this->assertFalse($this->_model->authenticate('User', \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD));
        $this->assertTrue(
            $this->_model->authenticate(
                \Magento\TestFramework\Bootstrap::ADMIN_NAME,
                \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
            )
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAdminConfigFixture admin/captcha/enable 0
     * @magentoConfigFixture current_store admin/security/use_case_sensitive_login 0
     */
    public function testAuthenticateCaseInsensitive()
    {
        $this->assertTrue($this->_model->authenticate('user', \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD));
        $this->assertTrue(
            $this->_model->authenticate(
                \Magento\TestFramework\Bootstrap::ADMIN_NAME,
                \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
            )
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedException \Magento\Framework\Exception\AuthenticationException
     * @magentoDbIsolation enabled
     */
    public function testAuthenticateInactiveUser()
    {
        $this->_model->load(1);
        $this->_model->setIsActive(0)->save();
        $this->_model->authenticate(
            \Magento\TestFramework\Bootstrap::ADMIN_NAME,
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\AuthenticationException
     * @magentoDbIsolation enabled
     */
    public function testAuthenticateUserWithoutRole()
    {
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $roles = $this->_model->getRoles();
        $this->_model->setRoleId(reset($roles))->deleteFromRole();
        $this->_model->authenticate(
            \Magento\TestFramework\Bootstrap::ADMIN_NAME,
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAdminConfigFixture admin/captcha/enable 0
     */
    public function testLoginsAreLogged()
    {
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $lognum = $this->_model->getLognum();

        $beforeLogin = time();
        $this->_model->login(
            \Magento\TestFramework\Bootstrap::ADMIN_NAME,
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        )->reload();
        $loginTime = strtotime($this->_model->getLogdate());

        $this->assertTrue($beforeLogin <= $loginTime && $loginTime <= time());
        $this->assertEquals(++$lognum, $this->_model->getLognum());

        $beforeLogin = time();
        $this->_model->login(
            \Magento\TestFramework\Bootstrap::ADMIN_NAME,
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        )->reload();
        $loginTime = strtotime($this->_model->getLogdate());
        $this->assertTrue($beforeLogin <= $loginTime && $loginTime <= time());
        $this->assertEquals(++$lognum, $this->_model->getLognum());
    }

    public function testReload()
    {
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $this->_model->setFirstname('NewFirstName');
        $this->assertEquals('NewFirstName', $this->_model->getFirstname());
        $this->_model->reload();
        $this->assertEquals(\Magento\TestFramework\Bootstrap::ADMIN_FIRSTNAME, $this->_model->getFirstname());
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testHasAssigned2Role()
    {
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $role = $this->_model->hasAssigned2Role($this->_model);
        $this->assertEquals(1, count($role));
        $this->assertArrayHasKey('role_id', $role[0]);
        $roles = $this->_model->getRoles();
        $this->_model->setRoleId(reset($roles))->deleteFromRole();
        $this->assertEmpty($this->_model->hasAssigned2Role($this->_model));
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage "User Name" is required. Enter and try again.
     * @expectedExceptionMessage "First Name" is required. Enter and try again.
     * @expectedExceptionMessage "Last Name" is required. Enter and try again.
     * @expectedExceptionMessage Please enter a valid email.
     * @expectedExceptionMessage "Password" is required. Enter and try again.
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveRequiredFieldsValidation()
    {
        $this->_model->setSomething('some_value');
        // force model change
        $this->_model->save();
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSavePasswordHash()
    {
        $pattern = $this->encryptor->getLatestHashVersion() === Encryptor::HASH_VERSION_ARGON2ID13 ?
            '/^[0-9a-f]+:[0-9a-zA-Z]{16}:[0-9]+$/' :
            '/^[0-9a-f]+:[0-9a-zA-Z]{32}:[0-9]+$/';
        $this->_model->setUsername(
            'john.doe'
        )->setFirstname(
            'John'
        )->setLastname(
            'Doe'
        )->setEmail(
            'jdoe@example.com'
        )->setPassword(
            '123123q'
        );
        $this->_model->save();
        $this->assertNotContains('123123q', $this->_model->getPassword(), 'Password is expected to be hashed');
        $this->assertRegExp(
            $pattern,
            $this->_model->getPassword(),
            'Salt is expected to be saved along with the password'
        );

        /** @var \Magento\User\Model\User $model */
        $model = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\User\Model\User::class);
        $model->load($this->_model->getId());
        $this->assertEquals(
            $this->_model->getPassword(),
            $model->getPassword(),
            'Password data has been corrupted during saving'
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Your password confirmation must match your password.
     * @magentoDbIsolation enabled
     */
    public function testBeforeSavePasswordsDoNotMatch()
    {
        $this->_model->setPassword('password2');
        $this->_model->setPasswordConfirmation('password1');
        $this->_model->save();
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Your password must include both numeric and alphabetic characters.
     * @magentoDbIsolation enabled
     */
    public function testBeforeSavePasswordTooShort()
    {
        $this->_model->setPassword('123456');
        $this->_model->save();
    }

    /**
     * @dataProvider beforeSavePasswordInsecureDataProvider
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Your password must include both numeric and alphabetic characters.
     * @magentoDbIsolation enabled
     * @param string $password
     */
    public function testBeforeSavePasswordInsecure($password)
    {
        $this->_model->setPassword($password);
        $this->_model->save();
    }

    public function beforeSavePasswordInsecureDataProvider()
    {
        return ['alpha chars only' => ['aaaaaaaa'], 'digits only' => ['1234567']];
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage A user with the same user name or email already exists.
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveUserIdentityViolation()
    {
        $this->_model->setUsername('user');
        $this->_model->save();
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testBeforeSaveValidationSuccess()
    {
        $this->_model->setUsername(
            'user1'
        )->setFirstname(
            'John'
        )->setLastname(
            'Doe'
        )->setEmail(
            'jdoe@example.com'
        )->setPassword(
            '1234abc'
        )->setPasswordConfirmation(
            '1234abc'
        );
        $this->_model->save();
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testChangeResetPasswordLinkToken()
    {
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $this->_model->changeResetPasswordLinkToken('test');
        $date = $this->_model->getRpTokenCreatedAt();
        $this->assertNotNull($date);
        $this->_model->save();
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $this->assertEquals('test', $this->_model->getRpToken());
        $this->assertEquals(strtotime($date), strtotime($this->_model->getRpTokenCreatedAt()));
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/admin/emails/password_reset_link_expiration_period 2
     */
    public function testIsResetPasswordLinkTokenExpired()
    {
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $this->assertTrue($this->_model->isResetPasswordLinkTokenExpired());
        $this->_model->changeResetPasswordLinkToken('test');
        $this->_model->save();
        $this->_model->loadByUsername(\Magento\TestFramework\Bootstrap::ADMIN_NAME);
        $this->assertFalse($this->_model->isResetPasswordLinkTokenExpired());
        $this->_model->setRpTokenCreatedAt($this->_dateTime->formatDate(time() - 60 * 60 * 2 + 2));
        $this->assertFalse($this->_model->isResetPasswordLinkTokenExpired());

        $this->_model->setRpTokenCreatedAt($this->_dateTime->formatDate(time() - 60 * 60 * 2 - 1));
        $this->assertTrue($this->_model->isResetPasswordLinkTokenExpired());
    }

    public function testGetSetHasAvailableResources()
    {
        $this->_model->setHasAvailableResources(true);
        $this->assertTrue($this->_model->hasAvailableResources());

        $this->_model->setHasAvailableResources(false);
        $this->assertFalse($this->_model->hasAvailableResources());
    }

    /**
     * Here we test if admin identity check executed successfully
     *
     * @magentoDataFixture Magento/User/_files/user_with_role.php
     */
    public function testPerformIdentityCheck()
    {
        $this->_model->loadByUsername('adminUser');
        $passwordString = \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD;
        $this->_model->performIdentityCheck($passwordString);
    }

    /**
     * Here we check for a wrong password
     *
     * @magentoDataFixture Magento/User/_files/user_with_role.php
     * @expectedException \Magento\Framework\Exception\AuthenticationException
     */
    public function testPerformIdentityCheckWrongPassword()
    {
        $this->_model->loadByUsername('adminUser');
        $passwordString = 'wrongPassword';
        $this->_model->performIdentityCheck($passwordString);

        $this->expectExceptionMessage(
            'The password entered for the current user is invalid. Verify the password and try again.'
        );
    }

    /**
     * Here we check for a locked user
     *
     * @magentoDataFixture Magento/User/_files/locked_users.php
     * @expectedException \Magento\Framework\Exception\State\UserLockedException
     */
    public function testPerformIdentityCheckLockExpires()
    {
        $this->_model->loadByUsername('adminUser2');
        $this->_model->performIdentityCheck(\Magento\TestFramework\Bootstrap::ADMIN_PASSWORD);

        $this->expectExceptionMessage(
            'The account sign-in was incorrect or your account is disabled temporarily. '
            . 'Please wait and try again later.'
        );
    }
}

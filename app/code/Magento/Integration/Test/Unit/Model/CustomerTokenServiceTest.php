<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Integration\Test\Unit\Model;

use Magento\Integration\Model\Integration;
use Magento\Integration\Model\Oauth\Token;

class CustomerTokenServiceTest extends \PHPUnit\Framework\TestCase
{
    /** \Magento\Integration\Model\CustomerTokenService */
    protected $_tokenService;

    /** \Magento\Integration\Model\Oauth\TokenFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $_tokenFactoryMock;

    /** \Magento\Customer\Api\AccountManagementInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $_accountManagementMock;

    /** \Magento\Integration\Model\ResourceModel\Oauth\Token\Collection|\PHPUnit_Framework_MockObject_MockObject */
    protected $_tokenModelCollectionMock;

    /** \PHPUnit_Framework_MockObject_MockObject */
    protected $_tokenModelCollectionFactoryMock;

    /** @var \Magento\Integration\Model\CredentialsValidator|\PHPUnit_Framework_MockObject_MockObject */
    protected $validatorHelperMock;

    /** @var \Magento\Integration\Model\Oauth\Token|\PHPUnit_Framework_MockObject_MockObject */
    private $_tokenMock;

    /** @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    protected function setUp()
    {
        $this->_tokenFactoryMock = $this->getMockBuilder(\Magento\Integration\Model\Oauth\TokenFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_tokenFactoryMock->expects($this->any())->method('create')->will($this->returnValue($this->_tokenMock));

        $this->_accountManagementMock = $this
            ->getMockBuilder(\Magento\Customer\Api\AccountManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_tokenMock = $this->getMockBuilder(\Magento\Integration\Model\Oauth\Token::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToken', 'loadByCustomerId', 'delete', '__wakeup'])->getMock();

        $this->_tokenModelCollectionMock = $this->getMockBuilder(
            \Magento\Integration\Model\ResourceModel\Oauth\Token\Collection::class
        )->disableOriginalConstructor()->setMethods(
            ['addFilterByCustomerId', 'getSize', '__wakeup', '_beforeLoad', '_afterLoad', 'getIterator', '_fetchAll']
        )->getMock();

        $this->_tokenModelCollectionFactoryMock = $this->getMockBuilder(
            \Magento\Integration\Model\ResourceModel\Oauth\Token\CollectionFactory::class
        )->setMethods(['create'])->disableOriginalConstructor()->getMock();

        $this->_tokenModelCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($this->_tokenModelCollectionMock));

        $this->validatorHelperMock = $this->getMockBuilder(
            \Magento\Integration\Model\CredentialsValidator::class
        )->disableOriginalConstructor()->getMock();

        $this->manager = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);

        $this->_tokenService = new \Magento\Integration\Model\CustomerTokenService(
            $this->_tokenFactoryMock,
            $this->_accountManagementMock,
            $this->_tokenModelCollectionFactoryMock,
            $this->validatorHelperMock,
            $this->manager
        );
    }

    public function testRevokeCustomerAccessToken()
    {
        $customerId = 1;

        $this->_tokenModelCollectionMock->expects($this->once())
            ->method('addFilterByCustomerId')
            ->with($customerId)
            ->will($this->returnValue($this->_tokenModelCollectionMock));
        $this->_tokenModelCollectionMock->expects($this->any())
            ->method('getSize')
            ->will($this->returnValue(1));
        $this->_tokenModelCollectionMock->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator([$this->_tokenMock])));
        $this->_tokenModelCollectionMock->expects($this->any())
            ->method('_fetchAll')
            ->will($this->returnValue(1));
        $this->_tokenMock->expects($this->once())
            ->method('delete')
            ->will($this->returnValue($this->_tokenMock));

        $this->assertTrue($this->_tokenService->revokeCustomerAccessToken($customerId));
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage This customer has no tokens.
     */
    public function testRevokeCustomerAccessTokenWithoutCustomerId()
    {
        $this->_tokenModelCollectionMock->expects($this->once())
            ->method('addFilterByCustomerId')
            ->with(null)
            ->will($this->returnValue($this->_tokenModelCollectionMock));
        $this->_tokenMock->expects($this->never())
            ->method('delete')
            ->will($this->returnValue($this->_tokenMock));
        $this->_tokenService->revokeCustomerAccessToken(null);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The tokens couldn't be revoked.
     */
    public function testRevokeCustomerAccessTokenCannotRevoked()
    {
        $exception = new \Exception();
        $customerId = 1;
        $this->_tokenModelCollectionMock->expects($this->once())
            ->method('addFilterByCustomerId')
            ->with($customerId)
            ->will($this->returnValue($this->_tokenModelCollectionMock));
        $this->_tokenModelCollectionMock->expects($this->once())
            ->method('getSize')
            ->will($this->returnValue(1));
        $this->_tokenModelCollectionMock->expects($this->once())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator([$this->_tokenMock])));

        $this->_tokenMock->expects($this->once())
            ->method('delete')
            ->will($this->throwException($exception));
        $this->_tokenService->revokeCustomerAccessToken($customerId);
    }
}

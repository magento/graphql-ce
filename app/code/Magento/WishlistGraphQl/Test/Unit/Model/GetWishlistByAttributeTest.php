<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\WishlistGraphQl\Model\GetWishlistByAttribute;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class GetWishlistByAttributeTest extends TestCase
{

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var GetWishlistByAttribute
     */
    protected $model;

    const NEW_WISHLIST_CUSTOMER_ID = 9999;

    const MOCK_SHARING_CODE = 'MOCK_SHARING_CODE';

    protected function setUp()
    {
        $wishlistModel = $this->getMockBuilder(Wishlist::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateSharingCode'])
            ->getMock();

        $wishlistModel->expects($this->any())
            ->method('generateSharingCode')
            ->willReturnCallback(function() use ($wishlistModel) {
                $wishlistModel->setSharingCode(self::MOCK_SHARING_CODE);
            });

        $wishlistResource = $this->getMockBuilder(WishlistResourceModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'save'])
            ->getMock();

        $wishlistResource->expects($this->any())
            ->method('load')
            ->willReturnCallback(function($wishlist, $value, $attribute) {
                $isWishlist = $attribute === 'wishlist_id';
                if ($isWishlist && $value === self::NEW_WISHLIST_CUSTOMER_ID) {
                    return $wishlist;
                }

                $wishlist->setData($attribute, $value);

                if ($isWishlist) {
                    $wishlist->setId($value);
                }

                return $wishlist;
            });

        $wishlistFactory = $this->getMockBuilder(WishlistFactory::class)
                ->disableOriginalConstructor()
                ->getMock();

        $wishlistFactory->expects(self::any())
                ->method('create')
                ->willReturn($wishlistModel);

        $this->objectManager = new ObjectManager($this);
        $this->model = $this->objectManager->getObject(
            GetWishlistByAttribute::class,
            [
                'wishlistResource' => $wishlistResource,
                'wishlistFactory' => $wishlistFactory
            ]
        );
    }

    public function testGetWishlistById()
    {
        $wishlistId = 1;
        $result = $this->model->execute('wishlist_id', $wishlistId);
        $this->assertEquals($wishlistId, $result->getId());
    }

    public function testGetWishlistByCustomerId()
    {
        $attributeName = 'customer_id';
        $customerId = 1;
        $result = $this->model->execute($attributeName, $customerId);
        $this->assertEquals($customerId, $result->getData($attributeName));
    }

    public function testCreateNewEntity()
    {
        $attributeName = 'customer_id';
        $customerId = self::NEW_WISHLIST_CUSTOMER_ID;
        $result = $this->model->execute($attributeName, $customerId, true);
        $this->assertEquals($customerId, $result->getData($attributeName));
        $this->assertEquals($result->getSharingCode(), self::MOCK_SHARING_CODE);
    }
}

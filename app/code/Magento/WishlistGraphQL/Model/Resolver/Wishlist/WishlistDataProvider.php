<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQL\Model\Resolver\Wishlist;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Magento\Wishlist\Model\Wishlist as WishlistModel;

/**
 * Wishlist field data provider, used for GraphQL request processing.
 */
class WishlistDataProvider
{
    /**
     * @var WishlistModel
     */
    private $wishListModel;

    /**
     * @var ServiceOutputProcessor
     */
    private $serviceOutputProcessor;

    /**
     * @var SerializerInterface
     */
    private $jsonSerializer;

    /**
     * @param WishlistModel $wishlistModel
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param SerializerInterface $jsonSerializer
     */
    public function __construct(
        WishlistModel $wishlistModel,
        ServiceOutputProcessor $serviceOutputProcessor,
        SerializerInterface $jsonSerializer
    ) {
        $this->wishListModel = $wishlistModel;
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->jsonSerializer = $jsonSerializer;
    }

    public function getWishListByCustomerId($id)
    {
        try {
            $wishList = $this->wishListModel->loadByCustomerId($id);
            if ($this->wishListModel->getItemsCount()) {
                $wishList->setItems($this->wishListModel->getItemCollection());
            }
        } catch (NoSuchEntityException $e) {
            // No error should be thrown, null result should be returned
            return [];
        }
        return $this->processWishList($wishList);
    }

    /**
     * @param WishlistModel $wishlistObject
     * @return array
     */
    private function processWishList(WishlistModel $wishlistObject) : array
    {
        $wishList = $wishlistObject->getData();
        if (isset($wishList['extension_attributes'])) {
            $wishList = array_merge($wishList, $wishList['extension_attributes']);
        }
        $customAttributes = [];
        if (isset($wishList['custom_attributes'])) {
            foreach ($wishList['custom_attributes'] as $attribute) {
                $isArray = false;
                if (is_array($attribute['value'])) {
                    $isArray = true;
                    foreach ($attribute['value'] as $attributeValue) {
                        if (is_array($attributeValue)) {
                            $customAttributes[$attribute['attribute_code']] = $this->jsonSerializer->serialize(
                                $attribute['value']
                            );
                            continue;
                        }
                        $customAttributes[$attribute['attribute_code']] = implode(',', $attribute['value']);
                        continue;
                    }
                }
                if ($isArray) {
                    continue;
                }
                $customAttributes[$attribute['attribute_code']] = $attribute['value'];
            }
        }
        $wishList = array_merge($wishList, $customAttributes);

        return $wishList;
    }
}

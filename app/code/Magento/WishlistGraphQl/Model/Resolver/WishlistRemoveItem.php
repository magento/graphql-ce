<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\WishlistGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Model\ResourceModel\Item\Collection as WishlistItemCollection;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory as WishlistItemCollectionFactory;
use Magento\Wishlist\Model\ResourceModel\Wishlist as WishlistResourceModel;
use Magento\Wishlist\Model\ResourceModel\Item as WishlistItemResourceModel;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

/**
 * Fetches the Wishlist data according to the GraphQL schema
 */
class WishlistRemoveItem implements ResolverInterface
{
    /**
     * @var WishlistResourceModel
     */
    private $wishlistResource;

    /**
     * @var WishlistItemResourceModel
     */
    private $wishlistItemResource;

    /**
     * @var WishlistFactory
     */
    private $wishlistFactory;

    /**
     * @var WishlistItemCollectionFactory
     */
    private $wishlistItemCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param WishlistResourceModel $wishlistResource
     * @param WishlistItemResourceModel $wishlistItemResource
     * @param WishlistFactory $wishlistFactory
     * @param WishlistItemCollectionFactory $wishlistItemCollectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        WishlistResourceModel $wishlistResource,
        WishlistItemResourceModel $wishlistItemResource,
        WishlistFactory $wishlistFactory,
        WishlistItemCollectionFactory $wishlistItemCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->wishlistResource = $wishlistResource;
        $this->wishlistItemResource = $wishlistItemResource;
        $this->wishlistFactory = $wishlistFactory;
        $this->wishlistItemCollectionFactory = $wishlistItemCollectionFactory;
        $this->storeManager = $storeManager;
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
        $wishlist = $this->getWishlistByContext($context->getUserId());

        if ($wishlist->getId() === null) {
            return [];
        }

        $this->deleteWishlistItems($wishlist, $args['input']);

        return [
            'sharing_code' => $wishlist->getSharingCode(),
            'updated_at' => $wishlist->getUpdatedAt(),
            'items_count' => $wishlist->getItemsCount(),
            'name' => $wishlist->getName(),
            'model' => $wishlist,
        ];
    }

    /**
     * Loads Wishlist by the context
     *
     * @param ContextInterface $context
     * @throws GraphQlAuthorizationException
     * @return Wishlist
     */
    private function getWishlistByContext($customerId): Wishlist
    {
        /* Guest checking */
        if (!$customerId && 0 === $customerId) {
            throw new GraphQlAuthorizationException(__('The current user cannot perform operations on wishlist'));
        }

        /** @var Wishlist $wishlist */
        $wishlist = $this->wishlistFactory->create();
        $this->wishlistResource->load($wishlist, $customerId, 'customer_id');
        return $wishlist;
    }

    /**
     * @param Wishlist $wishlist
     * @param array $ids
     * @throws \Exception
     */
    private function deleteWishlistItems(Wishlist $wishlist, Array $ids)
    {
        $wishlistItems = $this->getWishListItems($wishlist, $ids);

        foreach($wishlistItems as $whislistItem) {
            try {
                $this->wishlistItemResource->delete($whislistItem);
            } catch (\Exception $e) {
                throw new \Exception(__('Failed to delete the wishlist item'));
            }
        }
    }

    /**
     * @param Wishlist $wishlist
     * @param array $ids
     * @return array
     */
    private function getWishListItems(Wishlist $wishlist, Array $ids): array
    {
        /** @var WishlistItemCollection $wishlistItemCollection */
        $wishlistItemCollection = $this->wishlistItemCollectionFactory->create();
        $wishlistItemCollection
            ->addWishlistFilter($wishlist)
            ->addStoreFilter(array_map(function (StoreInterface $store) {
                return $store->getId();
            }, $this->storeManager->getStores()))
            ->setVisibilityFilter()
            ->addFieldToFilter('wishlist_item_id', array('in' => $ids));

        return $wishlistItemCollection->getItems();
    }
}

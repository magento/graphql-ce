<?php

declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Cart;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;

class GetAllCustomerCarts
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        $this->cartRepository = $cartRepository;
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * @param int $customerId
     * @return array
     */
    public function execute(int $customerId): array
    {
        $criteria = $this->criteriaBuilder
            ->addFilter('customer_id', $customerId)
            ->addFilter(CartInterface::KEY_IS_ACTIVE, 1)
            ->create();
        return $this->cartRepository->getList($criteria)->getItems();
    }
}

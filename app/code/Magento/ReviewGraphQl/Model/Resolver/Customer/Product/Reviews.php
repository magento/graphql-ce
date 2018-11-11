<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReviewGraphQl\Model\Resolver\Customer\Product;

use Magento\CustomerGraphQl\Model\Customer\CheckCustomerAccountInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\Argument\SearchCriteria\Builder as SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\ReviewGraphQl\Model\Resolver\Product\Reviews\Query\Filter;

/**
 * Class Reviews
 * @package Magento_ReviewGraphQl
 */
class Reviews implements ResolverInterface
{
    /**
     * @var CheckCustomerAccountInterface
     */
    private $checkCustomerAccount;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var Filter
     */
    private $filterQuery;

    /**
     * Reviews constructor
     *
     * @param CheckCustomerAccountInterface $checkCustomerAccount
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param FilterBuilder $filterBuilder
     * @param Filter $filterQuery
     */
    public function __construct(
        CheckCustomerAccountInterface $checkCustomerAccount,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        FilterBuilder $filterBuilder,
        Filter $filterQuery
    ) {
        $this->checkCustomerAccount = $checkCustomerAccount;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterQuery = $filterQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $customerId = (int)$context->getUserId();

        $this->checkCustomerAccount->execute($customerId, $context->getUserType());

        $searchCriteria = $this->searchCriteriaBuilder->build($field->getName(), $args);

        $customerFilter = $this->filterBuilder
            ->setField('customer_id')
            ->setValue($customerId)
            ->create();

        $filterGroups = $searchCriteria->getFilterGroups();
        $filterGroups[] = $this->filterGroupBuilder
            ->addFilter($customerFilter)
            ->create();

        $searchCriteria->setFilterGroups($filterGroups);
        $searchCriteria->setCurrentPage($args['currentPage']);
        $searchCriteria->setPageSize($args['pageSize']);

        $searchResult = $this->filterQuery->getResult($searchCriteria);

        if ($searchCriteria->getPageSize()) {
            $maxPages = ceil($searchResult->getTotalCount() / $searchCriteria->getPageSize());
        } else {
            $maxPages = 0;
        }

        $currentPage = $searchCriteria->getCurrentPage();
        if ($searchCriteria->getCurrentPage() > $maxPages && $searchResult->getTotalCount() > 0) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the %2 page(s) available.',
                    [$currentPage, $maxPages]
                )
            );
        }

        $data = [
            'total_count' => $searchResult->getTotalCount(),
            'items' => $searchResult->getReviewsSearchResult(),
            'page_info' => [
                'page_size' => $searchCriteria->getPageSize(),
                'current_page' => $currentPage,
                'total_pages' => $maxPages
            ],
        ];

        return $data;
    }
}

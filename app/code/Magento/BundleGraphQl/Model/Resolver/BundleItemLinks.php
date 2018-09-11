<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\BundleGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\BundleGraphQl\Model\Resolver\Links\Collection;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * @inheritdoc
 */
class BundleItemLinks implements ResolverInterface
{
    /**
     * @var Collection
     */
    private $linkCollection;

    /**
     * @param Collection $linkCollection
     */
    public function __construct(
        Collection $linkCollection
    ) {
        $this->linkCollection = $linkCollection;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['option_id']) || !isset($value['parent_id'])) {
            throw new GraphQlInputException(__('"option_id" and "parent_id" values should be specified'));
        }

        $this->linkCollection->addIdFilters((int)$value['option_id'], (int)$value['parent_id']);
        $result = function () use ($value) {
            return $this->linkCollection->getLinksForOptionId((int)$value['option_id']);
        };

        return $this->valueFactory->create($result);
    }
}

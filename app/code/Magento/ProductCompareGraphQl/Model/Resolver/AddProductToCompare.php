<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ProductCompareGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\ProductCompareGraphQl\Model\DataProvider\Visitor;
use Magento\Catalog\Model\Product\Compare\ItemFactory;

/**
 * Resolver for Add Product to Compare List mutation
 */
class AddProductToCompare implements ResolverInterface
{
    /**
     * @var ItemFactory
     */
    private $compareItemFactory;

    /**
     * @var Visitor
     */
    private $visitorDataProvider;

    /**
     * @param Visitor     $visitorDataProvider
     * @param ItemFactory $compareItemFactory
     */
    public function __construct(
        Visitor $visitorDataProvider,
        ItemFactory $compareItemFactory
    ) {
        $this->visitorDataProvider = $visitorDataProvider;
        $this->compareItemFactory = $compareItemFactory;
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
        if (!isset($args['input']) || !is_array($args['input']) || empty($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }
        $result = ['result' => false, "compareProducts" => []];
        if (!empty($args['input']['ids']) && is_array($args['input']['ids'])) {
            foreach ($args['input']['ids'] as $id) {
                $item = $this->compareItemFactory->create();
                $item = $this->visitorDataProvider->addUserToItem($item);
                $item->loadByProduct($id);
                if (!$item->getId()) {
                    $item->addProductData((int)$id);
                    $item->save();
                }
            }
            $result = ['result' => true, "compareProducts" => []];
        }
        return $result;
    }
}

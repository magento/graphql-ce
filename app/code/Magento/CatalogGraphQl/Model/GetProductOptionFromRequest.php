<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model;

use Magento\CatalogGraphQl\Model\ProductOption\ProductOptionInterface;
use Magento\CatalogGraphQl\Model\ProductOption\ProductOptionFactory;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Class getProductOptionFromRequest
 *
 * Extracts product option information from request string
 */
class GetProductOptionFromRequest
{
    /**
     * @var ProductOptionFactory
     */
    private $productOptionFactory;

    /**
     * getProductOptionFromRequest constructor.
     * @param ProductOptionFactory $productOptionFactory
     */
    public function __construct(
        ProductOptionFactory $productOptionFactory
    ) {
        $this->productOptionFactory = $productOptionFactory;
    }

    /**
     * Extracts product option information from request string
     *
     * @param array $request
     * @param string $source
     * @return ProductOptionInterface
     * @throws GraphQlInputException
     */
    public function execute(array $request, string $source): ProductOptionInterface
    {
        /** @var ProductOptionInterface $productOption */
        $productOption = $this->productOptionFactory->create();

        if ($source === ProductOptionInterface::SOURCE_ENTERED) {
            $optionInfo = explode('/', base64_decode($request['id']), 2);
            list($optionType, $optionId) = $optionInfo;
            $productOption->setType(ProductOptionInterface::SOURCE_ENTERED);
            $optionValue = base64_decode($request['value']);
        } elseif ($source === ProductOptionInterface::SOURCE_SELECTED) {
            $selectedOptionInfo = explode('/', base64_decode($request['value']), 3);
            list($optionType, $optionId, $optionValue) = $selectedOptionInfo;
            $productOption->setType(ProductOptionInterface::SOURCE_SELECTED);
        } else {
            throw new GraphQlInputException(__("Invalid source '$source' for product option"));
        }

        $productOption->setId((int)$optionId);
        $productOption->setType($optionType);
        $productOption->setValue($optionValue);

        return $productOption;
    }
}

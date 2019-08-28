<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogInventoryGraphQl\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockStatusRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Downloadable\Model\Product\Type as DownloadableProductType;

/**
 * @inheritdoc
 */
class StockStatusProvider implements ResolverInterface
{
    /**
     * @var StockStatusRepositoryInterface
     */
    private $stockStatusRepository;

    /**
     * @param StockStatusRepositoryInterface $stockStatusRepository
     */
    public function __construct(StockStatusRepositoryInterface $stockStatusRepository)
    {
        $this->stockStatusRepository = $stockStatusRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!array_key_exists('model', $value) || !$value['model'] instanceof ProductInterface) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /* @var $product ProductInterface */
        $product = $value['model'];

        $stockStatus = $this->stockStatusRepository->get($product->getId());
        $productStockStatus = (int)$stockStatus->getStockStatus();
        $stockStatusByProductType = StockStatusInterface::STATUS_IN_STOCK;

        if ($product->getTypeId() == DownloadableProductType::TYPE_DOWNLOADABLE) {
            /** @var DownloadableProductType $downloadableTypeInstance */
            $downloadableTypeInstance = $product->getTypeInstance();
            if (!$downloadableTypeInstance->hasLinks($product)) {
                $stockStatusByProductType = StockStatusInterface::STATUS_OUT_OF_STOCK;
            }
        }

        return
            $productStockStatus === StockStatusInterface::STATUS_IN_STOCK
            && $stockStatusByProductType === StockStatusInterface::STATUS_IN_STOCK
                ? 'IN_STOCK'
                : 'OUT_OF_STOCK';
    }
}

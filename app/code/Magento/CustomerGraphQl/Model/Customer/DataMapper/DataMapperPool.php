<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerGraphQl\Model\Customer\DataMapper;

use Magento\CustomerGraphQl\Api\DataMapperInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Data mapper pool, will perform the data mapping on customer response using the data mappers
 */
class DataMapperPool implements DataMapperInterface
{
    /**
     * Data mappers
     * @var array
     */
    private $dataMappers;

    /**
     * DataMapperPool constructor.
     * @param array $dataMappers
     */
    public function __construct(array $dataMappers = [])
    {
        $this->dataMappers = $dataMappers;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $data): array
    {
        foreach ($this->dataMappers as $dataMapper) {
            if (!$dataMapper instanceof DataMapperInterface) {
                throw new GraphQlInputException(
                    __('The data mapper does not implement %1', DataMapperInterface::class)
                );
            }

            $data = $dataMapper->execute($data);
        }

        return $data;
    }
}

<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GraphQlPlayground\ViewModel;

use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class GraphQlFrontName
 * @package Magento\GraphQlPlayground\ViewModel
 */
class GraphQlFrontName implements ArgumentInterface
{
    /**
     * @var AreaList
     */
    private $areaList;

    /** @var string */
    private $graphQlFrontName;

    /**
     * GraphQlFrontName constructor.
     *
     * @param AreaList $areaList
     */
    public function __construct(
        AreaList $areaList
    ) {
        $this->areaList = $areaList;
    }

    /**
     * get Graphql Area Front Name
     *
     * @return string
     */
    public function getGraphqlFrontName(): string
    {
        if (!$this->graphQlFrontName) {
            $this->graphQlFrontName = $this->areaList->getFrontName(Area::AREA_GRAPHQL);
        }
        return $this->graphQlFrontName;
    }
}

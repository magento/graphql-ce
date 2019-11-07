<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GraphQlPlayground\Block;

use Magento\Framework\App\Area;
use Magento\Framework\App\AreaList;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\Store;

/**
 * Class Playground
 *
 * @package Magento\GraphQlPlayground\Block
 */
class Playground extends Template
{
    /**
     * @var \Magento\Framework\App\AreaList
     */
    private $areaList;

    /**
     * @var string
     */
    private $graphqlEndpoint;

    public function __construct(
        AreaList $areaList,
        Template\Context $context,
        array $data = []
    ) {
        $this->areaList = $areaList;
        parent::__construct($context, $data);
    }

    public function getGraphqlEndpoint(): string
    {
        if (!$this->graphqlEndpoint || strlen($this->graphqlEndpoint) == 0) {
            $this->graphqlEndpoint =
                $this->_scopeConfig->getValue(Store::XML_PATH_SECURE_BASE_URL) .
                $this->areaList->getFrontName(Area::AREA_GRAPHQL);
        }
        return $this->graphqlEndpoint;
    }
}

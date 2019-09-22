<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\UrlRewriteGraphQl\Model\Resolver\UrlRewrite;

/**
 * Pool of custom route locators.
 */
class CustomRouteLocator implements CustomRouteLocatorInterface
{
    /**
     * @var array
     */
    private $routeLocators;

    /**
     * @param CustomRouteLocatorInterface[] $routeLocators
     */
    public function __construct(
        array $routeLocators = []
    ) {
        $this->routeLocators = $routeLocators;
    }

    /**
     * @inheritdoc
     */
    public function resolveRoute($urlKey): ?array
    {
        foreach ($this->routeLocators as $urlLocator) {
            $url = $urlLocator->locateRoute($urlKey);
            if ($url !== null) {
                return $url;
            }
        }
        return null;
    }
}

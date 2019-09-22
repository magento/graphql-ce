<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\UrlRewriteGraphQl\Model\Resolver\UrlRewrite;

/**
 * Interface for resolution of custom routes.
 *
 * It can be used, for example, to resolve '/news' URL path to a 'Blog News' page.
 */
interface CustomRouteLocatorInterface
{
    /**
     * Resolve route based on custom rules.
     *
     * @param string $urlKey
     * @return array|null Return null if route cannot be resolved
     */
    public function resolveRoute($urlKey): ?array;
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\UrlRewriteGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewriteGraphQl\Model\Resolver\UrlRewrite\CustomUrlLocatorInterface;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Model\Product\Url as ProductUrl;
use Magento\Catalog\Model\ProductFactory;

/**
 * UrlRewrite field resolver, used for GraphQL request processing.
 */
class EntityUrl implements ResolverInterface
{
    /**
     * Sanitized entity type value for cms page
     */
    const ENTITY_TYPE_CMS_PAGE = 'CMS_PAGE';

    /**
     * Sanitized entity type value for product
     */
    const ENTITY_TYPE_PRODUCT = 'PRODUCT';

    /**
     * Sanitized entity type value for category
     */
    const ENTITY_TYPE_CATEGORY = 'CATEGORY';

    /**
     * Product URL instance
     *
     * @var ProductUrl
     */
    private $productUrlModel;

    /**
     * @var UrlFinderInterface
     */
    private $urlFinder;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomUrlLocatorInterface
     */
    private $customUrlLocator;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * EntityUrl constructor.
     * @param UrlFinderInterface $urlFinder
     * @param StoreManagerInterface $storeManager
     * @param CustomUrlLocatorInterface $customUrlLocator
     * @param UrlInterface $url
     * @param ProductUrl $productUrlModel
     * @param ProductFactory $productFactory
     */
    public function __construct(
        UrlFinderInterface $urlFinder,
        StoreManagerInterface $storeManager,
        CustomUrlLocatorInterface $customUrlLocator,
        UrlInterface $url,
        ProductUrl $productUrlModel,
        ProductFactory $productFactory
    ) {
        $this->urlFinder = $urlFinder;
        $this->storeManager = $storeManager;
        $this->customUrlLocator = $customUrlLocator;
        $this->url = $url;
        $this->productUrlModel = $productUrlModel;
        $this->productFactory = $productFactory;
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
        if (!isset($args['url']) || empty(trim($args['url']))) {
            throw new GraphQlInputException(__('"url" argument should be specified and not empty'));
        }

        $result = null;
        $url = $args['url'];
        if (substr($url, 0, 1) === '/' && $url !== '/') {
            $url = ltrim($url, '/');
        }
        $customUrl = $this->customUrlLocator->locateUrl($url);
        $url = $customUrl ?: $url;
        $urlRewrite = $this->findUrlRewrite($url);
        if ($urlRewrite) {
            $result = [
                'entity_id'   => $urlRewrite->getEntityId(),
                'entity_type' => $this->sanitizeType($urlRewrite->getEntityType()),
                'url'         => [
                    'canonical' => $this->getCanonicalUrlByUrlRewrite($urlRewrite),
                    'system'    => $urlRewrite->getTargetPath(),
                ]
            ];
        }
        return $result;
    }

    /**
     * Find the canonical url passing through all redirects if any
     *
     * @param string $requestPath
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite|null
     */
    private function findUrlRewrite(string $requestPath) : ?\Magento\UrlRewrite\Service\V1\Data\UrlRewrite
    {
        $urlRewrite = $this->findUrlFromRequestPath($requestPath);
        if ($urlRewrite && $urlRewrite->getRedirectType() > 0) {
            while ($urlRewrite && $urlRewrite->getRedirectType() > 0) {
                $urlRewrite = $this->findUrlFromRequestPath($urlRewrite->getTargetPath());
            }
        }
        if (!$urlRewrite) {
            $urlRewrite = $this->findUrlFromTargetPath($requestPath);
        }

        return $urlRewrite;
    }

    /**
     * Find a url from a request url on the current store
     *
     * @param string $requestPath
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite|null
     */
    private function findUrlFromRequestPath(string $requestPath) : ?\Magento\UrlRewrite\Service\V1\Data\UrlRewrite
    {
        return $this->urlFinder->findOneByData(
            [
                'request_path' => $requestPath,
                'store_id' => $this->storeManager->getStore()->getId()
            ]
        );
    }

    /**
     * Find a url from a target url on the current store
     *
     * @param string $targetPath
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite|null
     */
    private function findUrlFromTargetPath(string $targetPath) : ?\Magento\UrlRewrite\Service\V1\Data\UrlRewrite
    {
        return $this->urlFinder->findOneByData(
            [
                'target_path' => $targetPath,
                'store_id' => $this->storeManager->getStore()->getId()
            ]
        );
    }

    /**
     * Sanitize the type to fit schema specifications
     *
     * @param string $type
     * @return string
     */
    private function sanitizeType(string $type) : string
    {
        return strtoupper(str_replace('-', '_', $type));
    }

    /**
     * Get canonical URL by URL rewrite in the way how it is done for frontend
     *
     * @param \Magento\UrlRewrite\Service\V1\Data\UrlRewrite $urlRewrite
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCanonicalUrlByUrlRewrite(\Magento\UrlRewrite\Service\V1\Data\UrlRewrite $urlRewrite) : string
    {
        $canonicalUrl = '';

        switch ($this->sanitizeType($urlRewrite->getEntityType())) {
            case self::ENTITY_TYPE_CATEGORY:
            case self::ENTITY_TYPE_CMS_PAGE:
                $canonicalUrl = $this->url->getDirectUrl($urlRewrite->getRequestPath());
                break;
            case self::ENTITY_TYPE_PRODUCT:
                $product = $this->productFactory->create();
                $product->setId($urlRewrite->getEntityId());
                $product->setStoreId($this->storeManager->getStore()->getId());
                $canonicalUrl = $this->productUrlModel->getUrl($product, ['_ignore_category' => true]);
                break;
        }

        return $canonicalUrl;
    }
}

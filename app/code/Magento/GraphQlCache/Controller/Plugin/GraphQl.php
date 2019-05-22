<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQlCache\Controller\Plugin;

use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\GraphQlCache\Model\CacheableQuery;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Controller\ResultInterface;
use Magento\PageCache\Model\Config;
use Magento\GraphQl\Controller\HttpRequestProcessor;
use Magento\Framework\App\Response\Http as ResponseHttp;

/**
 * Plugin for handling controller after controller tags and pre-controller validation.
 */
class GraphQl
{
    /**
     * @var CacheableQuery
     */
    private $cacheableQuery;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var HttpResponse
     */
    private $response;

    /**
     * @var HttpRequestProcessor
     */
    private $requestProcessor;

    /**
     * @param CacheableQuery $cacheableQuery
     * @param Config $config
     * @param HttpResponse $response
     * @param HttpRequestProcessor $requestProcessor
     */
    public function __construct(
        CacheableQuery $cacheableQuery,
        Config $config,
        HttpResponse $response,
        HttpRequestProcessor $requestProcessor
    ) {
        $this->cacheableQuery = $cacheableQuery;
        $this->config = $config;
        $this->response = $response;
        $this->requestProcessor = $requestProcessor;
    }

    /**
     * Process graphql headers
     *
     * @param FrontControllerInterface $subject
     * @param RequestInterface $request
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDispatch(
        FrontControllerInterface $subject,
        RequestInterface $request
    ) {
        /** @var \Magento\Framework\App\Request\Http $request */
        $this->requestProcessor->processHeaders($request);
    }

    /**
     * Plugin for GraphQL after render from dispatch to set tag and cache headers
     *
     * @param ResultInterface $subject
     * @param ResultInterface $result
     * @param ResponseHttp $response
     * @return ResultInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRenderResult(ResultInterface $subject, ResultInterface $result, ResponseHttp $response)
    {
        $sendNoCacheHeaders = false;
        if ($this->config->isEnabled()) {
            if ($this->cacheableQuery->shouldPopulateCacheHeadersWithTags()) {
                $this->response->setPublicHeaders($this->config->getTtl());
                $this->response->setHeader('X-Magento-Tags', implode(',', $this->cacheableQuery->getCacheTags()), true);
            } else {
                $sendNoCacheHeaders = true;
            }
        } else {
            $sendNoCacheHeaders = true;
        }

        if ($sendNoCacheHeaders) {
            $this->response->setNoCacheHeaders();
        }

        return $result;
    }
}

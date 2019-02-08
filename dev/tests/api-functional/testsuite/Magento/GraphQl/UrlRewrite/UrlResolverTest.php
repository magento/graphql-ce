<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\UrlRewrite;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CmsUrlRewrite\Model\CmsPageUrlRewriteGenerator;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\Cms\Helper\Page as PageHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\Product\Url as ProductUrl;
use Magento\Framework\UrlInterface;

/**
 * Test the GraphQL endpoint's URLResolver query to verify canonical URL's are correctly returned.
 */
class UrlResolverTest extends GraphQlAbstract
{
    /** @var  ObjectManager */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * Tests the use case where URL-rewrite request path is provided as resolver input in the Query
     *
     * @magentoApiDataFixture Magento/CatalogUrlRewrite/_files/product_with_category.php
     * @magentoConfigFixture default_store web/session/use_frontend_sid 0
     */
    public function testProductUrlResolverWithRequestPathArgument()
    {
        $productSku = 'p002';
        $urlPathes = ['p002.html', 'cat-1/p002.html'];
        /** @var ProductUrl $productUrlModel */
        $productUrlModel = $this->objectManager->get(ProductUrl::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($productSku, false, null, true);
        $storeId = $product->getStoreId();

        /** @var  UrlFinderInterface $urlFinder */
        $urlFinder = $this->objectManager->get(UrlFinderInterface::class);

        foreach ($urlPathes as $urlPath) {
            $actualUrls = $urlFinder->findOneByData(
                [
                    'request_path' => $urlPath,
                    'store_id'     => $storeId
                ]
            );
            $targetPath = $actualUrls->getTargetPath();
            $expectedType = $actualUrls->getEntityType();
            $expectedCanonicalUrl = $productUrlModel->getUrl($product, ['_ignore_category' => true]);
            $query
                = <<<QUERY
{
  urlResolver(url:"{$targetPath}")
  {
   entity_id
   entity_type
   url {
      system
      canonical
    }
  }
}
QUERY;
            $response = $this->graphQlQuery($query);
            $this->assertArrayHasKey('urlResolver', $response);
            $this->assertEquals($product->getEntityId(), $response['urlResolver']['entity_id']);
            $this->assertEquals($targetPath, $response['urlResolver']['url']['system']);
            $this->assertEquals($expectedCanonicalUrl, $response['urlResolver']['url']['canonical']);
            $this->assertEquals(strtoupper($expectedType), $response['urlResolver']['entity_type']);
        }
    }

    /**
     * Tests the use case where system path (target path) is provided as resolver input in the Query
     *
     * @magentoApiDataFixture Magento/CatalogUrlRewrite/_files/product_with_category.php
     * @magentoConfigFixture default_store web/session/use_frontend_sid 0
     */
    public function testProductUrlResolverWithTargetPathArgument()
    {
        $productSku = 'p002';
        $urlPath = 'p002.html';
        /** @var ProductUrl $productUrlModel */
        $productUrlModel = $this->objectManager->get(ProductUrl::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($productSku, false, null, true);
        $storeId = $product->getStoreId();

        /** @var  UrlFinderInterface $urlFinder */
        $urlFinder = $this->objectManager->get(UrlFinderInterface::class);
        $actualUrls = $urlFinder->findOneByData(
            [
                'request_path' => $urlPath,
                'store_id' => $storeId
            ]
        );
        $targetPath = $actualUrls->getTargetPath();
        $expectedType = $actualUrls->getEntityType();
        $expectedCanonicalUrl = $productUrlModel->getUrl($product, ['_ignore_category' => true]);
        $query
            = <<<QUERY
{
  urlResolver(url:"{$targetPath}")
  {
   entity_id
   entity_type
   url {
      system
      canonical
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertArrayHasKey('urlResolver', $response);
        $this->assertEquals($product->getEntityId(), $response['urlResolver']['entity_id']);
        $this->assertEquals($targetPath, $response['urlResolver']['url']['system']);
        $this->assertEquals($expectedCanonicalUrl, $response['urlResolver']['url']['canonical']);
        $this->assertEquals(strtoupper($expectedType), $response['urlResolver']['entity_type']);
    }

    /**
     * Test Category Url Resolver with request path argument
     *
     * @magentoApiDataFixture Magento/CatalogUrlRewrite/_files/product_with_category.php
     * @magentoConfigFixture default_store web/session/use_frontend_sid 0
     */
    public function testCategoryUrlResolverWithRequestPathArgument()
    {
        $productSku = 'p002';
        $urlPath = 'cat-1.html';
        /** @var UrlInterface $url */
        $url = $this->objectManager->get(UrlInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($productSku, false, null, true);
        $storeId = $product->getStoreId();

        /** @var  UrlFinderInterface $urlFinder */
        $urlFinder = $this->objectManager->get(UrlFinderInterface::class);
        $actualUrls = $urlFinder->findOneByData(
            [
                'request_path' => $urlPath,
                'store_id' => $storeId
            ]
        );
        $categoryId = $actualUrls->getEntityId();
        $targetPath = $actualUrls->getTargetPath();
        $requestPath = $actualUrls->getRequestPath();
        $expectedType = $actualUrls->getEntityType();
        $query
            = <<<QUERY
{
  urlResolver(url:"{$urlPath}")
  {
   entity_id
   entity_type
   url {
      system
      canonical
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertArrayHasKey('urlResolver', $response);
        $this->assertEquals($categoryId, $response['urlResolver']['entity_id']);
        $this->assertEquals($targetPath, $response['urlResolver']['url']['system']);
        $this->assertEquals($url->getDirectUrl($requestPath), $response['urlResolver']['url']['canonical']);
        $this->assertEquals(strtoupper($expectedType), $response['urlResolver']['entity_type']);
    }

    /**
     * Test Category Url Resolver with target path argument
     *
     * @magentoApiDataFixture Magento/CatalogUrlRewrite/_files/product_with_category.php
     * @magentoConfigFixture default_store web/session/use_frontend_sid 0
     */
    public function testCategoryUrlResolverWithTargetPathArgument()
    {
        $productSku = 'p002';
        $urlPath = 'cat-1.html';
        /** @var UrlInterface $url */
        $url = $this->objectManager->get(UrlInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($productSku, false, null, true);
        $storeId = $product->getStoreId();

        /** @var  UrlFinderInterface $urlFinder */
        $urlFinder = $this->objectManager->get(UrlFinderInterface::class);
        $actualUrls = $urlFinder->findOneByData(
            [
                'request_path' => $urlPath,
                'store_id' => $storeId
            ]
        );
        $categoryId = $actualUrls->getEntityId();
        $targetPath = $actualUrls->getTargetPath();
        $requestPath = $actualUrls->getRequestPath();
        $expectedType = $actualUrls->getEntityType();
        $query
            = <<<QUERY
{
  urlResolver(url:"{$targetPath}")
  {
   entity_id
   entity_type
   url {
      system
      canonical
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertArrayHasKey('urlResolver', $response);
        $this->assertEquals($categoryId, $response['urlResolver']['entity_id']);
        $this->assertEquals($targetPath, $response['urlResolver']['url']['system']);
        $this->assertEquals($url->getDirectUrl($requestPath), $response['urlResolver']['url']['canonical']);
        $this->assertEquals(strtoupper($expectedType), $response['urlResolver']['entity_type']);
    }

    /**
     * Test CMS page Url resolver with request path argument
     *
     * @magentoApiDataFixture Magento/Cms/_files/pages.php
     * @magentoConfigFixture default_store web/session/use_frontend_sid 0
     */
    public function testCMSPageUrlResolverWithRequestPathArgument()
    {
        /** @var \Magento\Cms\Model\Page $page */
        $page = $this->objectManager->get(\Magento\Cms\Model\Page::class);
        $page->load('page100');
        $cmsPageId = $page->getId();
        $requestPath = $page->getIdentifier();

        /** @var UrlInterface $url */
        $url = $this->objectManager->get(UrlInterface::class);

        /** @var \Magento\CmsUrlRewrite\Model\CmsPageUrlPathGenerator $urlPathGenerator */
        $urlPathGenerator = $this->objectManager->get(\Magento\CmsUrlRewrite\Model\CmsPageUrlPathGenerator::class);

        /** @param \Magento\Cms\Api\Data\PageInterface $page */
        $targetPath = $urlPathGenerator->getCanonicalUrlPath($page);
        $expectedEntityType = CmsPageUrlRewriteGenerator::ENTITY_TYPE;

        $query
            = <<<QUERY
{
  urlResolver(url:"{$requestPath}")
  {
   entity_id
   entity_type
   url {
      system
      canonical
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertEquals($cmsPageId, $response['urlResolver']['entity_id']);
        $this->assertEquals($targetPath, $response['urlResolver']['url']['system']);
        $this->assertEquals($url->getDirectUrl($requestPath), $response['urlResolver']['url']['canonical']);
        $this->assertEquals(strtoupper(str_replace('-', '_', $expectedEntityType)), $response['urlResolver']['entity_type']);
    }

    /**
     * Test CMS page Url resolver with target path argument
     *
     * @magentoApiDataFixture Magento/Cms/_files/pages.php
     * @magentoConfigFixture default_store web/session/use_frontend_sid 0
     */
    public function testCMSPageUrlResolverWithTargetPathArgument()
    {
        /** @var \Magento\Cms\Model\Page $page */
        $page = $this->objectManager->get(\Magento\Cms\Model\Page::class);
        $page->load('page100');
        $cmsPageId = $page->getId();
        $requestPath = $page->getIdentifier();

        /** @var UrlInterface $url */
        $url = $this->objectManager->get(UrlInterface::class);

        /** @var \Magento\CmsUrlRewrite\Model\CmsPageUrlPathGenerator $urlPathGenerator */
        $urlPathGenerator = $this->objectManager->get(\Magento\CmsUrlRewrite\Model\CmsPageUrlPathGenerator::class);

        /** @param \Magento\Cms\Api\Data\PageInterface $page */
        $targetPath = $urlPathGenerator->getCanonicalUrlPath($page);
        $expectedEntityType = CmsPageUrlRewriteGenerator::ENTITY_TYPE;

        $query
            = <<<QUERY
{
  urlResolver(url:"{$targetPath}")
  {
   entity_id
   entity_type
   url {
      system
      canonical
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertEquals($cmsPageId, $response['urlResolver']['entity_id']);
        $this->assertEquals($targetPath, $response['urlResolver']['url']['system']);
        $this->assertEquals($url->getDirectUrl($requestPath), $response['urlResolver']['url']['canonical']);
        $this->assertEquals(strtoupper(str_replace('-', '_', $expectedEntityType)), $response['urlResolver']['entity_type']);
    }

    /**
     * Tests the use case where the url_key of the existing product is changed (e. g. redirect)
     *
     * @magentoApiDataFixture Magento/CatalogUrlRewrite/_files/product_with_category.php
     * @magentoConfigFixture default_store web/session/use_frontend_sid 0
     */
    public function testProductUrlRewriteResolverWithRedirect()
    {
        $productSku = 'p002';
        $oldRequestPath = 'p002.html';
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($productSku, false, null, true);
        $storeId = $product->getStoreId();
        $product->setData('save_rewrites_history', 1);
        $product->setUrlKey('p002-new')->save();
        $urlPath = $product->getUrlKey() . '.html';
        $this->assertEquals($urlPath, 'p002-new.html');

        /** @var ProductUrl $productUrlModel */
        $productUrlModel = $this->objectManager->get(ProductUrl::class);

        /** @var  UrlFinderInterface $urlFinder */
        $urlFinder = $this->objectManager->get(UrlFinderInterface::class);
        $actualUrls = $urlFinder->findOneByData(
            [
                'request_path' => $urlPath,
                'store_id' => $storeId
            ]
        );
        $targetPath = $actualUrls->getTargetPath();
        $expectedType = $actualUrls->getEntityType();
        $expectedCanonicalUrl = $productUrlModel->getUrl($product, ['_ignore_category' => true]);
        $query
            = <<<QUERY
{
  urlResolver(url:"{$oldRequestPath}")
  {
   entity_id
   entity_type
   url {
      system
      canonical
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertArrayHasKey('urlResolver', $response);
        $this->assertEquals($product->getEntityId(), $response['urlResolver']['entity_id']);
        $this->assertEquals($targetPath, $response['urlResolver']['url']['system']);
        $this->assertEquals($expectedCanonicalUrl, $response['urlResolver']['url']['canonical']);
        $this->assertEquals(strtoupper($expectedType), $response['urlResolver']['entity_type']);
    }

    /**
     * Tests if null is returned when an invalid request_path is provided as input to urlResolver
     *
     * @magentoApiDataFixture Magento/CatalogUrlRewrite/_files/product_with_category.php
     */
    public function testInvalidUrlResolverInput()
    {
        $productSku = 'p002';
        $urlPath = 'p002';
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($productSku, false, null, true);
        $storeId = $product->getStoreId();

        /** @var  UrlFinderInterface $urlFinder */
        $urlFinder = $this->objectManager->get(UrlFinderInterface::class);
        $urlFinder->findOneByData(
            [
                'request_path' => $urlPath,
                'store_id' => $storeId
            ]
        );
        $query
            = <<<QUERY
{
  urlResolver(url:"{$urlPath}")
  {
   entity_id
   entity_type
   url {
      system
      canonical
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertArrayHasKey('urlResolver', $response);
        $this->assertNull($response['urlResolver']);
    }

    /**
     * Test for category entity with leading slash
     *
     * @magentoApiDataFixture Magento/CatalogUrlRewrite/_files/product_with_category.php
     * @magentoConfigFixture default_store web/session/use_frontend_sid 0
     */
    public function testCategoryUrlWithLeadingSlash()
    {
        $productSku = 'p002';
        $urlPath = 'cat-1.html';
        /** @var UrlInterface $url */
        $url = $this->objectManager->get(UrlInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $product = $productRepository->get($productSku, false, null, true);
        $storeId = $product->getStoreId();

        /** @var  UrlFinderInterface $urlFinder */
        $urlFinder = $this->objectManager->get(UrlFinderInterface::class);
        $actualUrls = $urlFinder->findOneByData(
            [
                'request_path' => $urlPath,
                'store_id' => $storeId
            ]
        );
        $categoryId = $actualUrls->getEntityId();
        $targetPath = $actualUrls->getTargetPath();
        $expectedType = $actualUrls->getEntityType();

        $query = <<<QUERY
{
  urlResolver(url:"{$urlPath}")
  {
   entity_id
   entity_type
   url {
      system
      canonical
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertArrayHasKey('urlResolver', $response);
        $this->assertEquals($categoryId, $response['urlResolver']['entity_id']);
        $this->assertEquals($targetPath, $response['urlResolver']['url']['system']);
        $this->assertEquals($url->getDirectUrl($urlPath), $response['urlResolver']['url']['canonical']);
        $this->assertEquals(strtoupper($expectedType), $response['urlResolver']['entity_type']);
    }

    /**
     * Test resolution of '/' path to home page
     */
    public function ignoretestResolveSlash()
    {
        /** @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface */
        $scopeConfigInterface = $this->objectManager->get(ScopeConfigInterface::class);
        /** @var UrlInterface $url */
        $url = $this->objectManager->get(UrlInterface::class);
        $homePageIdentifier = $scopeConfigInterface->getValue(
            PageHelper::XML_PATH_HOME_PAGE,
            ScopeInterface::SCOPE_STORE
        );
        /** @var \Magento\Cms\Model\Page $page */
        $page = $this->objectManager->get(\Magento\Cms\Model\Page::class);
        $page->load($homePageIdentifier);
        $homePageId = $page->getId();
        /** @var \Magento\CmsUrlRewrite\Model\CmsPageUrlPathGenerator $urlPathGenerator */
        $urlPathGenerator = $this->objectManager->get(\Magento\CmsUrlRewrite\Model\CmsPageUrlPathGenerator::class);
        /** @param \Magento\Cms\Api\Data\PageInterface $page */
        $targetPath = $urlPathGenerator->getCanonicalUrlPath($page);
        $query
            = <<<QUERY
{
  urlResolver(url:"/")
  {
   entity_id
   entity_type
   url {
      system
      canonical
    }
  }
}
QUERY;
        $response = $this->graphQlQuery($query);
        $this->assertArrayHasKey('urlResolver', $response);
        $this->assertEquals($homePageId, $response['urlResolver']['entity_id']);
        $this->assertEquals($targetPath, $response['urlResolver']['url']['system']);
        $this->assertEquals($url->getDirectUrl($homePageIdentifier), $response['urlResolver']['url']['canonical']);
        $this->assertEquals('CMS_PAGE', $response['urlResolver']['entity_type']);
    }
}

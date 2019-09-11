<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\DownloadableProduct;

use Magento\Downloadable\Api\LinkRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for downloadable product stock status.
 */
class DownloadableStockStatusTest extends GraphQlAbstract
{
    /**
     * @var LinkRepositoryInterface
     */
    private $linkRepository;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->linkRepository = $objectManager->get(LinkRepositoryInterface::class);
    }

    private function getQuery(string $sku): string
    {
        return <<<QUERY
{
  products(
    search: "$sku"
  ) {
    items {
      ... on DownloadableProduct {
        stock_status
        sku
      }
    }
  }
}
QUERY;
    }

    /**
     * @magentoApiDataFixture Magento/Downloadable/_files/downloadable_product_with_files_and_sample_url.php
     */
    public function testDownloadableWithNoLinksStockStatus()
    {
        $sku = 'downloadable-product';

        //remove links
        $linksList = $this->linkRepository->getList($sku);
        foreach ($linksList as $item) {
            $this->linkRepository->delete($item->getId());
        }

        $query = $this->getQuery($sku);
        $response = $this->graphQlQuery($query);

        $this->assertArrayHasKey(0, $response['products']['items']);
        $this->assertArrayHasKey('stock_status', $response['products']['items'][0]);
        $this->assertEquals('OUT_OF_STOCK', $response['products']['items'][0]['stock_status']);
    }

    /**
     * @magentoApiDataFixture Magento/Downloadable/_files/downloadable_product_with_files_and_sample_url.php
     */
    public function testDownloadableStockStatus()
    {
        $sku = 'downloadable-product';

        $query = $this->getQuery($sku);
        $response = $this->graphQlQuery($query);

        $this->assertArrayHasKey(0, $response['products']['items']);
        $this->assertArrayHasKey('stock_status', $response['products']['items'][0]);
        $this->assertEquals('IN_STOCK', $response['products']['items'][0]['stock_status']);
    }
}

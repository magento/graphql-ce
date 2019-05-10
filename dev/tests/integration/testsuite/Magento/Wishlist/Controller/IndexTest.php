<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Wishlist\Controller;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messages;

    /**
     * @var \Magento\Customer\Helper\View
     */
    protected $_customerViewHelper;

    protected function setUp()
    {
        parent::setUp();
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->_customerSession = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            \Magento\Customer\Model\Session::class,
            [$logger]
        );
        /** @var \Magento\Customer\Api\AccountManagementInterface $service */
        $service = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Customer\Api\AccountManagementInterface::class
        );
        $customer = $service->authenticate('customer@example.com', 'password');
        $this->_customerSession->setCustomerDataAsLoggedIn($customer);

        $this->_customerViewHelper = $this->_objectManager->create(\Magento\Customer\Helper\View::class);

        $this->_messages = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            \Magento\Framework\Message\ManagerInterface::class
        );
    }

    protected function tearDown()
    {
        $this->_customerSession->logout();
        $this->_customerSession = null;
        parent::tearDown();
    }

    /**
     * Verify wishlist view action
     *
     * The following is verified:
     * - \Magento\Wishlist\Model\ResourceModel\Item\Collection
     * - \Magento\Wishlist\Block\Customer\Wishlist
     * - \Magento\Wishlist\Block\Customer\Wishlist\Items
     * - \Magento\Wishlist\Block\Customer\Wishlist\Item\Column
     * - \Magento\Wishlist\Block\Customer\Wishlist\Item\Column\Cart
     * - \Magento\Wishlist\Block\Customer\Wishlist\Item\Column\Comment
     * - \Magento\Wishlist\Block\Customer\Wishlist\Button
     * - that \Magento\Wishlist\Block\Customer\Wishlist\Item\Options doesn't throw a fatal error
     *
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     */
    public function testItemColumnBlock()
    {
        $this->dispatch('wishlist/index/index');
        $body = $this->getResponse()->getBody();
        $this->assertEquals(
            1,
            \Magento\TestFramework\Helper\Xpath::getElementsCountForXpath(
                '//img[contains(@src, "small_image.jpg") and @alt = "Simple Product"]',
                $body
            )
        );
        $this->assertEquals(
            1,
            \Magento\TestFramework\Helper\Xpath::getElementsCountForXpath(
                '//textarea[contains(@name, "description")]',
                $body
            )
        );
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple_xss.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoAppArea frontend
     */
    public function testAddActionProductNameXss()
    {
        /** @var \Magento\Framework\Data\Form\FormKey $formKey */
        $formKey = $this->_objectManager->get(\Magento\Framework\Data\Form\FormKey::class);
        $this->getRequest()->setPostValue([
            'form_key' => $formKey->getFormKey(),
        ]);

        /** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
        $productRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);

        $product = $productRepository->get('product-with-xss');

        $this->dispatch('wishlist/index/add/product/' . $product->getId() . '?nocookie=1');

        $this->assertSessionMessages(
            $this->equalTo(
                [
                    "\n&lt;script&gt;alert(&quot;xss&quot;);&lt;/script&gt; has been added to your Wish List. "
                    . 'Click <a href="http://localhost/index.php/">here</a> to continue shopping.',
                ]
            ),
            \Magento\Framework\Message\MessageInterface::TYPE_SUCCESS
        );
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento/Wishlist/_files/wishlist_with_product_qty_increments.php
     */
    public function testAllcartAction()
    {
        $formKey = $this->_objectManager->get(\Magento\Framework\Data\Form\FormKey::class)->getFormKey();
        $this->getRequest()->setMethod('POST');
        $this->getRequest()->setParam('form_key', $formKey);
        $this->dispatch('wishlist/index/allcart');

        /** @var \Magento\Checkout\Model\Cart $cart */
        $cart = $this->_objectManager->get(\Magento\Checkout\Model\Cart::class);
        $quoteCount = $cart->getQuote()->getItemsCollection()->count();

        $this->assertEquals(0, $quoteCount);
        $this->assertSessionMessages(
            $this->contains('You can buy this product only in quantities of 5 at a time for "Simple Product".'),
            \Magento\Framework\Message\MessageInterface::TYPE_ERROR
        );
    }

    /**
     * @magentoDataFixture Magento/Wishlist/_files/wishlist.php
     */
    public function testSendAction()
    {
        \Magento\TestFramework\Helper\Bootstrap::getInstance()
            ->loadArea(\Magento\Framework\App\Area::AREA_FRONTEND);

        $request = [
            'form_key' => $this->_objectManager->get(\Magento\Framework\Data\Form\FormKey::class)->getFormKey(),
            'emails' => 'test@tosend.com',
            'message' => 'message',
            'rss_url' => null, // no rss
        ];

        $this->getRequest()->setPostValue($request);
        $this->getRequest()->setMethod('POST');

        $this->_objectManager->get(\Magento\Framework\Registry::class)->register(
            'wishlist',
            $this->_objectManager->get(\Magento\Wishlist\Model\Wishlist::class)->loadByCustomerId(1)
        );
        $this->dispatch('wishlist/index/send');

        /** @var \Magento\TestFramework\Mail\Template\TransportBuilderMock $transportBuilder */
        $transportBuilder = $this->_objectManager->get(
            \Magento\TestFramework\Mail\Template\TransportBuilderMock::class
        );

        $actualResult = quoted_printable_decode($transportBuilder->getSentMessage()->getRawMessage());

        $this->assertStringMatchesFormat(
            '%A' . $this->_customerViewHelper->getCustomerName($this->_customerSession->getCustomerDataObject())
            . ' wants to share this Wish List%A',
            $actualResult
        );
    }
}

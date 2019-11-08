<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Checkout\Test\TestCase;

use Magento\Mtf\TestCase\Scenario;
use Magento\Mtf\Util\Command\Cli\EnvWhitelist;

/**
 * Preconditions:
 * 1. Configure shipping method.
 * 2. Configure payment method.
 * 3. Create products.
 * 4. Create and setup customer.
 * 5. Create sales rule according to dataset.
 *
 * Steps:
 * 1. Go to Frontend.
 * 2. Add products to the cart.
 * 3. Apply discounts in shopping cart according to dataset.
 * 4. In 'Estimate Shipping and Tax' section specify destination using values from Test Data.
 * 5. Click the 'Get a Quote' button.
 * 6. In the section appeared select Shipping method, click the 'Update Total' button.
 * 7. Click the 'Proceed to Checkout' button.
 * 8. Select checkout method according to dataset.
 * 9. Fill billing information and select the 'Ship to this address' option.
 * 10. Select shipping method.
 * 11. Select payment method (use reward points and store credit if available).
 * 12. Verify order total on review step.
 * 13. Place order.
 * 14. Perform assertions.
 *
 * @group One_Page_Checkout
 * @ZephyrId MAGETWO-27485
 */
class OnePageCheckoutOfflinePaymentMethodsTest extends Scenario
{
    /* tags */
    const MVP = 'yes';
    const TEST_TYPE = 'acceptance_test, extended_acceptance_test';
    const SEVERITY = 'S0';
    /* end tags */

    /**
     * DomainWhitelist CLI
     *
     * @var EnvWhitelist
     */
    private $envWhitelist;

    /**
     * Perform needed injections
     *
     * @param EnvWhitelist $envWhitelist
     */
    public function __inject(EnvWhitelist $envWhitelist)
    {
        $this->envWhitelist = $envWhitelist;
    }

    /**
     * Runs one page checkout test.
     *
     * @return void
     */
    public function test()
    {
        $this->envWhitelist->addHost('example.com');
        $this->executeScenario();
    }

    /**
     * Clean data after running test.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->envWhitelist->removeHost('example.com');
    }
}

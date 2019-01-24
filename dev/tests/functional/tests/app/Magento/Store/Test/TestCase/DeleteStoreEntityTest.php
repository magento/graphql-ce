<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Store\Test\TestCase;

use Magento\Backend\Test\Page\Adminhtml\EditStore;
use Magento\Backend\Test\Page\Adminhtml\StoreDelete;
use Magento\Backend\Test\Page\Adminhtml\StoreIndex;
use Magento\Backup\Test\Page\Adminhtml\BackupIndex;
use Magento\Store\Test\Fixture\Store;
use Magento\Mtf\TestCase\Injectable;
use Magento\Config\Test\TestStep\SetupConfigurationStep;

/**
 * Test Creation for DeleteStoreEntity
 *
 * Test Flow:
 * Preconditions:
 * 1. Create store view
 *
 * Steps:
 * 1. Open Backend
 * 2. Go to Stores -> All Stores
 * 3. Open created store view
 * 4. Click "Delete Store View"
 * 5. Set "Create DB Backup" according to dataset
 * 6. Click "Delete Store View" - Warning message "This operation can take a long time" appears.
 * 7. Click "OK".
 * 8. Perform all assertions
 *
 * @group Store_Management
 * @ZephyrId MAGETWO-27942
 */
class DeleteStoreEntityTest extends Injectable
{
    /* tags */
    const MVP = 'yes';
    const SEVERITY = 'S2';
    /* end tags */

    /**
     * Page BackupIndex
     *
     * @var BackupIndex
     */
    protected $backupIndex;

    /**
     * Page StoreIndex
     *
     * @var StoreIndex
     */
    protected $storeIndex;

    /**
     * Page EditStore
     *
     * @var EditStore
     */
    protected $editStore;

    /**
     * Page StoreDelete
     *
     * @var StoreDelete
     */
    protected $storeDelete;

    /**
     * Prepare pages for test
     *
     * @param BackupIndex $backupIndex
     * @param StoreIndex $storeIndex
     * @param EditStore $editStore
     * @param StoreDelete $storeDelete
     * @return void
     */
    public function __inject(
        BackupIndex $backupIndex,
        StoreIndex $storeIndex,
        EditStore $editStore,
        StoreDelete $storeDelete
    ) {
        $this->storeIndex = $storeIndex;
        $this->editStore = $editStore;
        $this->backupIndex = $backupIndex;
        $this->storeDelete = $storeDelete;
    }

    /**
     * Run Delete Store Entity test
     *
     * @param Store $store
     * @param string $createBackup
     * @return void
     */
    public function test(Store $store, $createBackup)
    {
        // Preconditions:
        $store->persist();
        /** @var SetupConfigurationStep $enableBackupsStep */
        $enableBackupsStep = $this->objectManager->create(
            SetupConfigurationStep::class,
            ['configData' => 'enable_backups_functionality']
        );
        $enableBackupsStep->run();
        $this->backupIndex->open()
            ->getBackupGrid()
            ->massaction([], 'Delete', true, 'Select All');

        // Steps:
        $this->storeIndex->open();
        $this->storeIndex->getStoreGrid()->searchAndOpenStore($store);
        $this->editStore->getFormPageActions()->delete();
        $this->storeDelete->getStoreForm()->fillForm(['create_backup' => $createBackup]);
        $this->storeDelete->getFormPageActions()->delete();
        $this->storeDelete->getModalBlock()->acceptAlert();
    }
}

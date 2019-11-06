<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Setup\Validator;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\ConfigOptionsListConstants as ConfigOption;
use Magento\Setup\Model\AdminAccount;
use Magento\Setup\Model\ConfigOptionsList\DriverOptions;

/**
 * Admin user credentials validator
 */
class AdminCredentialsValidator
{
    /**
     * @var \Magento\Setup\Module\ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var \Magento\Setup\Model\AdminAccountFactory
     */
    private $adminAccountFactory;

    /**
     * @var \Magento\Setup\Module\SetupFactory
     */
    private $setupFactory;

    /**
     * @var DriverOptions
     */
    private $driverOptions;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Setup\Model\AdminAccountFactory $adminAccountFactory
     * @param \Magento\Setup\Module\ConnectionFactory $connectionFactory
     * @param \Magento\Setup\Module\SetupFactory $setupFactory
     * @param DriverOptions|null $driverOptions
     */
    public function __construct(
        \Magento\Setup\Model\AdminAccountFactory $adminAccountFactory,
        \Magento\Setup\Module\ConnectionFactory $connectionFactory,
        \Magento\Setup\Module\SetupFactory $setupFactory,
        DriverOptions $driverOptions = null
    ) {
        $this->connectionFactory = $connectionFactory;
        $this->adminAccountFactory = $adminAccountFactory;
        $this->setupFactory = $setupFactory;
        $this->driverOptions = $driverOptions ?? ObjectManager::getInstance()->get(DriverOptions::class);
    }

    /**
     * Validate admin user name and email.
     *
     * @param array $data
     * @return void
     * @throws \Exception
     */
    public function validate(array $data)
    {
        $driverOptions = $this->driverOptions->getDriverOptions($data);
        $dbConnection = $this->connectionFactory->create(
            [
                ConfigOption::KEY_NAME => $data[ConfigOption::INPUT_KEY_DB_NAME],
                ConfigOption::KEY_HOST => $data[ConfigOption::INPUT_KEY_DB_HOST],
                ConfigOption::KEY_USER => $data[ConfigOption::INPUT_KEY_DB_USER],
                ConfigOption::KEY_PASSWORD => $data[ConfigOption::INPUT_KEY_DB_PASSWORD],
                ConfigOption::KEY_PREFIX => $data[ConfigOption::INPUT_KEY_DB_PREFIX],
                ConfigOption::KEY_DRIVER_OPTIONS => $driverOptions
            ]
        );

        $adminAccount = $this->adminAccountFactory->create(
            $dbConnection,
            [
                AdminAccount::KEY_USER => $data[AdminAccount::KEY_USER],
                AdminAccount::KEY_EMAIL => $data[AdminAccount::KEY_EMAIL],
                AdminAccount::KEY_PASSWORD => $data[AdminAccount::KEY_PASSWORD],
                AdminAccount::KEY_PREFIX => $data[ConfigOption::INPUT_KEY_DB_PREFIX]
            ]
        );

        $adminAccount->validateUserMatches();
    }
}

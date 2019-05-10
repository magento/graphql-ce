<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AuthorizenetAcceptjs\Gateway\Command;

use Magento\AuthorizenetAcceptjs\Gateway\SubjectReader;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\CommandInterface;

/**
 * Chooses the best method of returning the payment based on the status of the transaction
 */
class RefundTransactionStrategyCommand implements CommandInterface
{
    private const REFUND = 'refund_settled';
    private const VOID = 'void';

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @param CommandPoolInterface $commandPool
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        SubjectReader $subjectReader
    ) {
        $this->commandPool = $commandPool;
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $commandSubject): void
    {
        $command = $this->getCommand($commandSubject);

        $this->commandPool->get($command)
            ->execute($commandSubject);
    }

    /**
     * Determines the command that should be used based on the status of the transaction
     *
     * @param array $commandSubject
     * @return string
     * @throws CommandException
     */
    private function getCommand(array $commandSubject): string
    {
        $details = $this->commandPool->get('get_transaction_details')
            ->execute($commandSubject)
            ->get();

        if ($details['transaction']['transactionStatus'] === 'capturedPendingSettlement') {
            return self::VOID;
        } elseif ($details['transaction']['transactionStatus'] !== 'settledSuccessfully') {
            throw new CommandException(__('This transaction cannot be refunded with its current status.'));
        }

        return self::REFUND;
    }
}

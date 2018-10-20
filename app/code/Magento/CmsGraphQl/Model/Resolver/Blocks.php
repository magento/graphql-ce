<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsGraphQl\Model\Resolver;

use Magento\CmsGraphQl\Model\Resolver\DataProvider\Block as BlockDataProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * CMS blocks field resolver, used for GraphQL request processing
 */
class Blocks implements ResolverInterface
{
    /**
     * @var BlockDataProvider
     */
    private $blockDataProvider;

    /**
     * Error message
     *
     * @var array
     */
    private $errorMessage = [];

    /**
     * @param BlockDataProvider $blockDataProvider
     */
    public function __construct(
        BlockDataProvider $blockDataProvider
    ) {
        $this->blockDataProvider = $blockDataProvider;
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

        $blockIdentifiers = $this->getBlockIdentifiers($args);
        $blocksData = $this->getBlocksData($blockIdentifiers);

        $resultData = [
            'items' => $blocksData,
            'errors' => $this->errorMessage
        ];
        return $resultData;
    }

    /**
     * Get block identifiers
     *
     * @param array $args
     * @return string[]
     * @throws GraphQlInputException
     */
    private function getBlockIdentifiers(array $args): array
    {
        if (!isset($args['identifiers']) || !is_array($args['identifiers']) || count($args['identifiers']) === 0) {
            throw new GraphQlInputException(__('"identifiers" of CMS blocks should be specified'));
        }

        return $args['identifiers'];
    }

    /**
     * Get blocks data
     *
     * @param array $blockIdentifiers
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    private function getBlocksData(array $blockIdentifiers): array
    {
        $blocksData = [];
        try {
            foreach ($blockIdentifiers as $blockIdentifier) {
                $blockData = $this->blockDataProvider->getData($blockIdentifier);
                if (!empty($blockData)) {
                    $blocksData[$blockIdentifier] = $blockData;
                } else {
                    $this->setErrorMessage(sprintf('The CMS block with the "%s" ID is disabled.', $blockIdentifier));
                }
            }
        } catch (NoSuchEntityException $e) {
            $this->setErrorMessage($e->getMessage());
        }
        return $blocksData;
    }

    /**
     * Set error message
     *
     * @param string $error
     * @return array
     */
    private function setErrorMessage(string $error): array
    {
        $this->errorMessage[]['message'] = $error;
        return $this->errorMessage;
    }

}

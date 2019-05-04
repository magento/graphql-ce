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
        $scopeIdentifiers  = $this->getScopeIdentifiers($args);
        $blocksData = $this->getBlocksData($blockIdentifiers,$scopeIdentifiers[0]);
        $resultData = [
            'items' => $blocksData,
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
     * Get scope identifiers
     *
     * @param array $args
     * @return string[]
     * @throws GraphQlInputException
     */
	private function getScopeIdentifiers(array $args): array
    {
        if (!isset($args['scope_code']) || !is_array($args['scope_code']) || count($args['scope_code']) === 0) {
            throw new GraphQlInputException(__('Scope Code of CMS blocks should be specified'));
        }
        return $args['scope_code'];
    }
    
    /**
     * Get blocks data
     *
     * @param array $blockIdentifiers
     * @param string $scopeargs
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    private function getBlocksData(array $blockIdentifiers,$scopeargs): array
    {
        $blocksData = [];
        foreach ($blockIdentifiers as $blockIdentifier) {
            try {
                $blocksData[$blockIdentifier] = $this->blockDataProvider->getData($blockIdentifier,$scopeargs);
            } catch (NoSuchEntityException $e) {
                $blocksData[$blockIdentifier] = new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
            }
        }
        return $blocksData;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CmsGraphQl\Model\Resolver\DataProvider;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Widget\Model\Template\FilterEmulate;
use Magento\Cms\Model\GetBlockByIdentifier;
/**
 * Cms block data provider
 */
class Block
{
	/**
	 * @var BlockRepositoryInterface
	 */
	private $blockRepository;
	
    /**
     * @var GetBlockByIdentifier
     */
    private $blockIdentifier;

    /**
     * @var FilterEmulate
     */
    private $widgetFilter;
    
    /**
     * @var \Magento\Store\Model\Store
     */
    private $store;

    /**
     * @param BlockRepositoryInterface $blockRepository
     * @param FilterEmulate $widgetFilter
     * @param GetBlockByIdentifier $widgetFilter
     * @param \Magento\Store\Model\Store $store
     * 
     */
    
    public function __construct(
    	BlockRepositoryInterface $blockRepository,
        FilterEmulate $widgetFilter,
    	GetBlockByIdentifier $blockIdentifier,
    	\Magento\Store\Model\Store $store
    ) {
    	$this->blockRepository = $blockRepository;
        $this->widgetFilter = $widgetFilter;
        $this->blockIdentifier = $blockIdentifier;
        $this->store = $store;
    }

    /**
     * Get block data
     *
     * @param string $blockIdentifier
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData(string $blockIdentifier,string $scopeargs): array
    {
        if($scopeargs==null){
        	$block = $this->blockRepository->getById($blockIdentifier);
        }else{
        	$store = $this->store->load($scopeargs,'code');
        	if(!$store->getId()){
        		throw new NoSuchEntityException(
        				__('Store View Does Not Exist')
        		);
        	}
        	$block = $this->blockIdentifier->execute($blockIdentifier,(int)$store->getId());
        }
        	
        if (false === $block->isActive()) {
        	throw new NoSuchEntityException(
        			__('The CMS block with the "%1" ID doesn\'t exist.', $blockIdentifier)
        	);
        }
        $renderedContent = $this->widgetFilter->filter($block->getContent());

        $blockData = [
            BlockInterface::IDENTIFIER => $block->getIdentifier(),
            BlockInterface::TITLE => $block->getTitle(),
            BlockInterface::CONTENT => $renderedContent,
        ];
        return $blockData;
    }
}

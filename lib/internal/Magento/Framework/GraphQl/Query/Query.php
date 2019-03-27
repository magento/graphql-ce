<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Framework\GraphQl\Query;

class Query implements QueryInterface
{
    /**
     * @var array
     */
    private $arguments;

    /**
     * @inheritdoc
     * @return array|null
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    public function getArgument(string $name)
    {
        return isset($this->arguments[$name]) ? $this->arguments[$name] : null;
    }

    /**
     * @inheritdoc
     */
    public function getStructure()
    {
        // TODO: Implement getStructure() method.
        // Basically, this method should return the same information that currently
        // \Magento\Framework\GraphQl\Schema\Type\ResolveInfo contain
    }

    public function setStructure()
    {

    }
}

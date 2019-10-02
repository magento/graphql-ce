<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\ProductOption;

/**
 * @inheritDoc
 */
class ProductOption implements ProductOptionInterface
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $source;

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var string|null
     */
    private $value;

    /**
     * @inheritDoc
     */
    public function setSource(string $source): ProductOptionInterface
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * @inheritDoc
     */
    public function setValue(string $value): ProductOptionInterface
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function setType(string $type): ProductOptionInterface
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function setId(int $id): ProductOptionInterface
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getId(): ?int
    {
        return $this->id;
    }
}

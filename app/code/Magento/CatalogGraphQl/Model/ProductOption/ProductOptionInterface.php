<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogGraphQl\Model\ProductOption;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface ProductOptionInterface
 *
 * Represents product option (customizable option, super attribute for configurable product, etc)
 */
interface ProductOptionInterface extends ExtensibleDataInterface
{
    const SOURCE_SELECTED = 'selected';

    const SOURCE_ENTERED = 'entered';

    const TYPE_CONFIGURABLE = 'configurable';

    const TYPE_CUSTOM = 'custom-option';

    /**
     * @param string $source
     * @return ProductOptionInterface
     */
    public function setSource(string $source) :self;

    /**
     * @return string|null
     */
    public function getSource(): ?string;

    /**
     * @param string $type
     * @return ProductOptionInterface
     */
    public function setType(string $type): self;

    /**
     * @return string|null
     */
    public function getType(): ?string;

    /**
     * @param string $value
     * @return ProductOptionInterface
     */
    public function setValue(string $value): self;

    /**
     * @return string|null
     */
    public function getValue(): ?string;

    /**
     * @param int $id
     * @return ProductOptionInterface
     */
    public function setId(int $id): self;

    /**
     * @return int|null
     */
    public function getId(): ?int;
}

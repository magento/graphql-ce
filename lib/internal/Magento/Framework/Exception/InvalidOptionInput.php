<?php
/**
 * InvalidOptionInput Exception
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Exception;

use Magento\Framework\Phrase;

/**
 * InvalidOptionInput exception
 *
 * @api
 */
class InvalidOptionInput extends LocalizedException
{
    /**
     * InvalidOptionInput constructor.
     *
     * @param \Magento\Framework\Phrase $phrase
     * @param \Exception|null $cause
     * @param int $code
     */
    public function __construct(\Magento\Framework\Phrase $phrase, \Exception $cause = null, int $code = 0)
    {
        $arguments = $phrase->getArguments();
        if (isset($arguments)) {
            $this->phrase = $phrase;
            $phrase = new Phrase($this->getExtendedMessage());
        }
        parent::__construct($phrase, $cause, $code);
    }

    /**
     * Add more information to exception message for custom option of product
     *
     * @return string
     */
    public function getExtendedMessage()
    {
        $exceptionTextArray = explode("\n", $this->phrase->getText());
        $extendedData = $this->phrase->getArguments();
        foreach ($exceptionTextArray as $key => &$value) {
            // @codingStandardsIgnoreLine
            $value .= " Please check input data: Product SKU - '{$extendedData[$key]['sku']}', Option ID - '{$extendedData[$key]['optionId']}'";
        }

        return __(implode("\n", $exceptionTextArray));
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Template constructions filter
 */
namespace Magento\Framework\Filter;

/**
 * Template filter
 *
 * @api
 */
class Template implements \Zend_Filter_Interface
{
    /**
     * Construction regular expression
     */
    const CONSTRUCTION_PATTERN = '/{{([a-z]{0,10})(.*?)}}/si';

    /**
     * Construction `depend` regular expression
     */
    const CONSTRUCTION_DEPEND_PATTERN = '/{{depend\s*(.*?)}}(.*?){{\\/depend\s*}}/si';

    /**
     * Construction `if` regular expression
     */
    const CONSTRUCTION_IF_PATTERN = '/{{if\s*(.*?)}}(.*?)({{else}}(.*?))?{{\\/if\s*}}/si';

    /**
     * Construction `template` regular expression
     */
    const CONSTRUCTION_TEMPLATE_PATTERN = '/{{(template)(.*?)}}/si';

    /**
     * Construction `for` regular expression
     */
    const LOOP_PATTERN = '/{{for(?P<loopItem>.*? )(in)(?P<loopData>.*?)}}(?P<loopBody>.*?){{\/for}}/si';

    /**#@-*/
    private $afterFilterCallbacks = [];

    /**
     * Assigned template variables
     *
     * @var array
     */
    protected $templateVars = [];

    /**
     * Template processor
     *
     * @var callable|null
     */
    protected $templateProcessor = null;

    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param array $variables
     */
    public function __construct(\Magento\Framework\Stdlib\StringUtils $string, $variables = [])
    {
        $this->string = $string;
        $this->setVariables($variables);
    }

    /**
     * Sets template variables that's can be called through {var ...} statement
     *
     * @param array $variables
     * @return \Magento\Framework\Filter\Template
     */
    public function setVariables(array $variables)
    {
        foreach ($variables as $name => $value) {
            $this->templateVars[$name] = $value;
        }
        return $this;
    }

    /**
     * Sets the processor for template directive.
     *
     * @param callable $callback it must return string
     * @return $this
     */
    public function setTemplateProcessor(callable $callback)
    {
        $this->templateProcessor = $callback;
        return $this;
    }

    /**
     * Sets the processor for template directive.
     *
     * @return callable|null
     */
    public function getTemplateProcessor()
    {
        return is_callable($this->templateProcessor) ? $this->templateProcessor : null;
    }

    /**
     * Filter the string as template.
     *
     * @param string $value
     * @return string
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function filter($value)
    {
        // "depend", "if", and "template" directives should be first
        foreach ([
                     self::CONSTRUCTION_DEPEND_PATTERN => 'dependDirective',
                     self::CONSTRUCTION_IF_PATTERN => 'ifDirective',
                     self::CONSTRUCTION_TEMPLATE_PATTERN => 'templateDirective',
                 ] as $pattern => $directive) {
            if (preg_match_all($pattern, $value, $constructions, PREG_SET_ORDER)) {
                foreach ($constructions as $construction) {
                    $callback = [$this, $directive];
                    if (!is_callable($callback)) {
                        continue;
                    }
                    try {
                        $replacedValue = call_user_func($callback, $construction);
                    } catch (\Exception $e) {
                        throw $e;
                    }
                    $value = str_replace($construction[0], $replacedValue, $value);
                }
            }
        }

        $value = $this->filterFor($value);

        if (preg_match_all(self::CONSTRUCTION_PATTERN, $value, $constructions, PREG_SET_ORDER)) {
            foreach ($constructions as $construction) {
                $callback = [$this, $construction[1] . 'Directive'];
                if (!is_callable($callback)) {
                    continue;
                }
                try {
                    $replacedValue = call_user_func($callback, $construction);
                } catch (\Exception $e) {
                    throw $e;
                }
                $value = str_replace($construction[0], $replacedValue, $value);
            }
        }

        $value = $this->afterFilter($value);
        return $value;
    }

    /**
     * Filter the string as template.
     *
     * @param string $value
     * @example syntax {{for item in order.items}} name: {{var item.name}} {{/for}} order items collection.
     * @example syntax {{for thing in things}} {{var thing.whatever}} {{/for}} e.g.:custom collection.
     * @return string
     */
    private function filterFor($value)
    {
        if (preg_match_all(self::LOOP_PATTERN, $value, $constructions, PREG_SET_ORDER)) {
            foreach ($constructions as $construction) {
                if (!$this->isValidLoop($construction)) {
                    return $value;
                }

                $fullTextToReplace = $construction[0];
                $loopData = $this->getVariable($construction['loopData'], '');

                $loopTextToReplace = $construction['loopBody'];
                $loopItemVariableName = preg_replace('/\s+/', '', $construction['loopItem']);

                if (is_array($loopData) || $loopData instanceof \Traversable) {
                    $replaceText = $this->getLoopReplacementText($loopData, $loopItemVariableName, $loopTextToReplace);
                    $value = str_replace($fullTextToReplace, $replaceText, $value);
                }
            }
        }

        return $value;
    }

    /**
     * Check if the matched construction is valid.
     *
     * @param array $construction
     * @return bool
     */
    private function isValidLoop(array $construction)
    {
        $requiredFields = ['loopBody', 'loopItem', 'loopData'];
        $validFields = array_filter(
            $requiredFields,
            function ($field) use ($construction) {
                return isset($construction[$field]) && strlen(trim($construction[$field]));
            }
        );
        return count($requiredFields) == count($validFields);
    }

    /**
     * Runs callbacks that have been added to filter content after directive processing is finished.
     *
     * @param string $value
     * @return string
     */
    protected function afterFilter($value)
    {
        foreach ($this->afterFilterCallbacks as $callback) {
            $value = call_user_func($callback, $value);
        }
        // Since a single instance of this class can be used to filter content multiple times, reset callbacks to
        // prevent callbacks running for unrelated content (e.g., email subject and email body)
        $this->resetAfterFilterCallbacks();
        return $value;
    }

    /**
     * Adds a callback to run after main filtering has happened.
     *
     * Callback must accept a single argument and return a string of the processed value.
     *
     * @param callable $afterFilterCallback
     * @return $this
     */
    public function addAfterFilterCallback(callable $afterFilterCallback)
    {
        // Only add callback if it doesn't already exist
        if (in_array($afterFilterCallback, $this->afterFilterCallbacks)) {
            return $this;
        }

        $this->afterFilterCallbacks[] = $afterFilterCallback;
        return $this;
    }

    /**
     * Resets the after filter callbacks
     *
     * @return $this
     */
    protected function resetAfterFilterCallbacks()
    {
        $this->afterFilterCallbacks = [];
        return $this;
    }

    /**
     * Get var directive
     *
     * @param string[] $construction
     * @return string
     */
    public function varDirective($construction)
    {
        if (count($this->templateVars) == 0) {
            // If template prepossessing
            return $construction[0];
        }

        $replacedValue = $this->getVariable($construction[2], '');
        return $replacedValue;
    }

    /**
     * Allows templates to be included inside other templates
     *
     * Usage:
     *
     *     {{template config_path="<PATH>"}}
     *
     * <PATH> equals the XPATH to the system configuration value that contains the value of the template.
     * This directive is useful to include things like a global header/footer.
     *
     * @param string[] $construction
     * @return mixed
     */
    public function templateDirective($construction)
    {
        // Processing of {template config_path=... [...]} statement
        $templateParameters = $this->getParameters($construction[2]);
        if (!isset($templateParameters['config_path']) || !$this->getTemplateProcessor()) {
            // Not specified template or not set include processor
            $replacedValue = '{Error in template processing}';
        } else {
            // Including of template
            $configPath = $templateParameters['config_path'];
            unset($templateParameters['config_path']);
            $templateParameters = array_merge_recursive($templateParameters, $this->templateVars);
            $replacedValue = call_user_func($this->getTemplateProcessor(), $configPath, $templateParameters);
        }
        return $replacedValue;
    }

    /**
     * Get depend directive
     *
     * @param string[] $construction
     * @return string
     */
    public function dependDirective($construction)
    {
        if (count($this->templateVars) == 0) {
            // If template processing
            return $construction[0];
        }

        if ($this->getVariable($construction[1], '') == '') {
            return '';
        } else {
            return $construction[2];
        }
    }

    /**
     * If directive
     *
     * @param string[] $construction
     * @return string
     */
    public function ifDirective($construction)
    {
        if (count($this->templateVars) == 0) {
            return $construction[0];
        }

        if ($this->getVariable($construction[1], '') == '') {
            if (isset($construction[3]) && isset($construction[4])) {
                return $construction[4];
            }
            return '';
        } else {
            return $construction[2];
        }
    }

    /**
     * Return associative array of parameters.
     *
     * @param string $value raw parameters
     * @return array
     */
    protected function getParameters($value)
    {
        $tokenizer = new Template\Tokenizer\Parameter();
        $tokenizer->setString($value);
        $params = $tokenizer->tokenize();
        foreach ($params as $key => $value) {
            if (substr($value, 0, 1) === '$') {
                $params[$key] = $this->getVariable(substr($value, 1), null);
            }
        }
        return $params;
    }

    /**
     * Return variable value for var construction
     *
     * @param string $value raw parameters
     * @param string $default default value
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getVariable($value, $default = '{no_value_defined}')
    {
        \Magento\Framework\Profiler::start('email_template_processing_variables');
        $tokenizer = new Template\Tokenizer\Variable();
        $tokenizer->setString($value);
        $stackVars = $tokenizer->tokenize();
        $result = $default;
        $last = 0;
        for ($i = 0, $count = count($stackVars); $i < $count; $i++) {
            if ($i == 0 && isset($this->templateVars[$stackVars[$i]['name']])) {
                // Getting of template value
                $stackVars[$i]['variable'] = & $this->templateVars[$stackVars[$i]['name']];
            } elseif (isset($stackVars[$i - 1]['variable'])
                    && $stackVars[$i - 1]['variable'] instanceof \Magento\Framework\DataObject
            ) {
                // If data object calling methods or getting properties
                if ($stackVars[$i]['type'] == 'property') {
                    $caller = 'get' . $this->string->upperCaseWords($stackVars[$i]['name'], '_', '');
                    $stackVars[$i]['variable'] = method_exists(
                        $stackVars[$i - 1]['variable'],
                        $caller
                    ) ? $stackVars[$i - 1]['variable']->{$caller}() : $stackVars[$i - 1]['variable']->getData(
                        $stackVars[$i]['name']
                    );
                } elseif ($stackVars[$i]['type'] == 'method') {
                    // Calling of data object method
                    if (method_exists($stackVars[$i - 1]['variable'], $stackVars[$i]['name'])
                            || substr($stackVars[$i]['name'], 0, 3) == 'get'
                    ) {
                        $stackVars[$i]['args'] = $this->getStackArgs($stackVars[$i]['args']);
                        $stackVars[$i]['variable'] = call_user_func_array(
                            [$stackVars[$i - 1]['variable'], $stackVars[$i]['name']],
                            $stackVars[$i]['args']
                        );
                    }
                }
                $last = $i;
            } elseif (isset($stackVars[$i - 1]['variable']) && $stackVars[$i]['type'] == 'method') {
                // Calling object methods
                if (method_exists($stackVars[$i - 1]['variable'], $stackVars[$i]['name'])) {
                    $stackVars[$i]['args'] = $this->getStackArgs($stackVars[$i]['args']);
                    $stackVars[$i]['variable'] = call_user_func_array(
                        [$stackVars[$i - 1]['variable'], $stackVars[$i]['name']],
                        $stackVars[$i]['args']
                    );
                }
                $last = $i;
            }
        }

        if (isset($stackVars[$last]['variable'])) {
            // If value for construction exists set it
            $result = $stackVars[$last]['variable'];
        }
        \Magento\Framework\Profiler::stop('email_template_processing_variables');
        return $result;
    }

    /**
     * Loops over a set of stack args to process variables into array argument values
     *
     * @param array $stack
     * @return array
     */
    protected function getStackArgs($stack)
    {
        foreach ($stack as $i => $value) {
            if (is_array($value)) {
                $stack[$i] = $this->getStackArgs($value);
            } elseif (substr($value, 0, 1) === '$') {
                $stack[$i] = $this->getVariable(substr($value, 1), null);
            }
        }
        return $stack;
    }

    /**
     * Process loop text to replace.
     *
     * @param array $loopData
     * @param string $loopItemVariableName
     * @param string $loopTextToReplace
     * @return string
     */
    private function getLoopReplacementText(array $loopData, $loopItemVariableName, $loopTextToReplace)
    {
        $loopText = [];
        $loopIndex = 0;
        $loopDataObject = new \Magento\Framework\DataObject();

        foreach ($loopData as $loopItemDataObject) {
            // Loop item can be an array or DataObject.
            // If loop item is an array, convert it to DataObject
            // to have unified interface if the collection
            if (!$loopItemDataObject instanceof \Magento\Framework\DataObject) {
                if (!is_array($loopItemDataObject)) {
                    continue;
                }
                $loopItemDataObject = new \Magento\Framework\DataObject($loopItemDataObject);
            }

            $loopDataObject->setData('index', $loopIndex++);
            $this->templateVars['loop'] = $loopDataObject;
            $this->templateVars[$loopItemVariableName] = $loopItemDataObject;

            if (preg_match_all(
                self::CONSTRUCTION_PATTERN,
                $loopTextToReplace,
                $attributes,
                PREG_SET_ORDER
            )
            ) {
                $subText = $loopTextToReplace;
                foreach ($attributes as $attribute) {
                    $text = $this->getVariable($attribute[2], '');
                    $subText = str_replace($attribute[0], $text, $subText);
                }
                $loopText[] = $subText;
            }
            unset($this->templateVars[$loopItemVariableName]);
        }
        $replaceText = implode('', $loopText);
        return $replaceText;
    }
}

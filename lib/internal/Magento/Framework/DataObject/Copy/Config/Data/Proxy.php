<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\DataObject\Copy\Config\Data;

/**
 * Proxy class for @see \Magento\Framework\DataObject\Copy\Config\Data
 */
class Proxy extends \Magento\Framework\DataObject\Copy\Config\Data implements
    \Magento\Framework\ObjectManager\NoninterceptableInterface
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Proxied instance name
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Proxied instance
     *
     * @var \Magento\Framework\DataObject\Copy\Config\Data
     */
    protected $_subject = null;

    /**
     * Instance shareability flag
     *
     * @var bool
     */
    protected $_isShared = null;

    /**
     * Proxy constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     * @param bool $shared
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = \Magento\Framework\DataObject\Copy\Config\Data::class,
        $shared = true
    ) {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
        $this->_isShared = $shared;
    }

    /**
     * Remove links to other objects.
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.SerializationAware)
     * @deprecated Do not use PHP serialization.
     */
    public function __sleep()
    {
        trigger_error('Using PHP serialization is deprecated', E_USER_DEPRECATED);

        return ['_subject', '_isShared'];
    }

    /**
     * Retrieve ObjectManager from global scope
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.SerializationAware)
     * @deprecated Do not use PHP serialization.
     */
    public function __wakeup()
    {
        trigger_error('Using PHP serialization is deprecated', E_USER_DEPRECATED);

        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    }

    /**
     * Clone proxied instance
     *
     * @return void
     */
    public function __clone()
    {
        $this->_subject = clone $this->_getSubject();
    }

    /**
     * Get proxied instance
     *
     * @return \Magento\Framework\DataObject\Copy\Config\Data
     */
    protected function _getSubject()
    {
        if (!$this->_subject) {
            $this->_subject = true === $this->_isShared
                ? $this->_objectManager->get($this->_instanceName)
                : $this->_objectManager->create($this->_instanceName);
        }
        return $this->_subject;
    }

    /**
     * @inheritdoc
     */
    public function merge(array $config)
    {
        return $this->_getSubject()->merge($config);
    }

    /**
     * @inheritdoc
     */
    public function get($path = null, $default = null)
    {
        return $this->_getSubject()->get($path, $default);
    }

    /**
     * @inheritdoc
     */
    public function reset()
    {
        return $this->_getSubject()->reset();
    }
}

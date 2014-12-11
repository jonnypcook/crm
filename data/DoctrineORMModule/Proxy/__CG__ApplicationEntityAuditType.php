<?php

namespace DoctrineORMModule\Proxy\__CG__\Application\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class AuditType extends \Application\Entity\AuditType implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Persistence\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Common\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array properties to be lazy loaded, with keys being the property
     *            names and values being their default values
     *
     * @see \Doctrine\Common\Persistence\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = array();



    /**
     * @param \Closure $initializer
     * @param \Closure $cloner
     */
    public function __construct($initializer = null, $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }







    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return array('__isInitialized__', '' . "\0" . 'Application\\Entity\\AuditType' . "\0" . 'name', '' . "\0" . 'Application\\Entity\\AuditType' . "\0" . 'icon', '' . "\0" . 'Application\\Entity\\AuditType' . "\0" . 'box', '' . "\0" . 'Application\\Entity\\AuditType' . "\0" . 'auto', '' . "\0" . 'Application\\Entity\\AuditType' . "\0" . 'auditTypeId');
        }

        return array('__isInitialized__', '' . "\0" . 'Application\\Entity\\AuditType' . "\0" . 'name', '' . "\0" . 'Application\\Entity\\AuditType' . "\0" . 'icon', '' . "\0" . 'Application\\Entity\\AuditType' . "\0" . 'box', '' . "\0" . 'Application\\Entity\\AuditType' . "\0" . 'auto', '' . "\0" . 'Application\\Entity\\AuditType' . "\0" . 'auditTypeId');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (AuditType $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy->__getLazyProperties() as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', array());
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', array());
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized)
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null)
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer()
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null)
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner()
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @static
     */
    public function __getLazyProperties()
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function getName()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getName', array());

        return parent::getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getAuditTypeId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getAuditTypeId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAuditTypeId', array());

        return parent::getAuditTypeId();
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setName', array($name));

        return parent::setName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function setAuditTypeId($auditTypeId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAuditTypeId', array($auditTypeId));

        return parent::setAuditTypeId($auditTypeId);
    }

    /**
     * {@inheritDoc}
     */
    public function getAuto()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAuto', array());

        return parent::getAuto();
    }

    /**
     * {@inheritDoc}
     */
    public function setAuto($auto)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAuto', array($auto));

        return parent::setAuto($auto);
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIcon', array());

        return parent::getIcon();
    }

    /**
     * {@inheritDoc}
     */
    public function setIcon($icon)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setIcon', array($icon));

        return parent::setIcon($icon);
    }

    /**
     * {@inheritDoc}
     */
    public function getBox()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getBox', array());

        return parent::getBox();
    }

    /**
     * {@inheritDoc}
     */
    public function setBox($box)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setBox', array($box));

        return parent::setBox($box);
    }

}
<?php

namespace DoctrineORMModule\Proxy\__CG__\Product\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Legacy extends \Product\Entity\Legacy implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'description', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'quantity', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'pwr_item', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'pwr_ballast', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'emergency', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'dim_item', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'dim_unit', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'created', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'category', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'product', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'attributes', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'legacyId');
        }

        return array('__isInitialized__', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'description', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'quantity', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'pwr_item', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'pwr_ballast', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'emergency', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'dim_item', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'dim_unit', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'created', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'category', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'product', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'attributes', '' . "\0" . 'Product\\Entity\\Legacy' . "\0" . 'legacyId');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Legacy $proxy) {
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
    public function getProduct()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getProduct', array());

        return parent::getProduct();
    }

    /**
     * {@inheritDoc}
     */
    public function setProduct($product)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setProduct', array($product));

        return parent::setProduct($product);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDescription', array());

        return parent::getDescription();
    }

    /**
     * {@inheritDoc}
     */
    public function getQuantity()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getQuantity', array());

        return parent::getQuantity();
    }

    /**
     * {@inheritDoc}
     */
    public function getPwr_item()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPwr_item', array());

        return parent::getPwr_item();
    }

    /**
     * {@inheritDoc}
     */
    public function getPwr_ballast()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPwr_ballast', array());

        return parent::getPwr_ballast();
    }

    /**
     * {@inheritDoc}
     */
    public function getCreated()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCreated', array());

        return parent::getCreated();
    }

    /**
     * {@inheritDoc}
     */
    public function getCategory()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCategory', array());

        return parent::getCategory();
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAttributes', array());

        return parent::getAttributes();
    }

    /**
     * {@inheritDoc}
     */
    public function getDim_item()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDim_item', array());

        return parent::getDim_item();
    }

    /**
     * {@inheritDoc}
     */
    public function getDim_unit()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getDim_unit', array());

        return parent::getDim_unit();
    }

    /**
     * {@inheritDoc}
     */
    public function getEmergency()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEmergency', array());

        return parent::getEmergency();
    }

    /**
     * {@inheritDoc}
     */
    public function getLegacyId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getLegacyId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLegacyId', array());

        return parent::getLegacyId();
    }

    /**
     * {@inheritDoc}
     */
    public function setDescription($description)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDescription', array($description));

        return parent::setDescription($description);
    }

    /**
     * {@inheritDoc}
     */
    public function setQuantity($quantity)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setQuantity', array($quantity));

        return parent::setQuantity($quantity);
    }

    /**
     * {@inheritDoc}
     */
    public function setPwr_item($pwr_item)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPwr_item', array($pwr_item));

        return parent::setPwr_item($pwr_item);
    }

    /**
     * {@inheritDoc}
     */
    public function setPwr_ballast($pwr_ballast)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPwr_ballast', array($pwr_ballast));

        return parent::setPwr_ballast($pwr_ballast);
    }

    /**
     * {@inheritDoc}
     */
    public function setCreated(\DateTime $created)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCreated', array($created));

        return parent::setCreated($created);
    }

    /**
     * {@inheritDoc}
     */
    public function setCategory(\DateTime $category)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCategory', array($category));

        return parent::setCategory($category);
    }

    /**
     * {@inheritDoc}
     */
    public function setAttributes($attributes)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAttributes', array($attributes));

        return parent::setAttributes($attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function setDim_item($dim_item)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDim_item', array($dim_item));

        return parent::setDim_item($dim_item);
    }

    /**
     * {@inheritDoc}
     */
    public function setDim_unit($dim_unit)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setDim_unit', array($dim_unit));

        return parent::setDim_unit($dim_unit);
    }

    /**
     * {@inheritDoc}
     */
    public function setEmergency($emergency)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setEmergency', array($emergency));

        return parent::setEmergency($emergency);
    }

    /**
     * {@inheritDoc}
     */
    public function setLegacyId($legacyId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLegacyId', array($legacyId));

        return parent::setLegacyId($legacyId);
    }

    /**
     * {@inheritDoc}
     */
    public function getTotalPwr()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getTotalPwr', array());

        return parent::getTotalPwr();
    }

}

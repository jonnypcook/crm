<?php

namespace DoctrineORMModule\Proxy\__CG__\Application\Entity;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Competitor extends \Application\Entity\Competitor implements \Doctrine\ORM\Proxy\Proxy
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
            return array('__isInitialized__', '' . "\0" . 'Application\\Entity\\Competitor' . "\0" . 'name', '' . "\0" . 'Application\\Entity\\Competitor' . "\0" . 'url', '' . "\0" . 'Application\\Entity\\Competitor' . "\0" . 'strengths', '' . "\0" . 'Application\\Entity\\Competitor' . "\0" . 'weaknesses', 'projects', '' . "\0" . 'Application\\Entity\\Competitor' . "\0" . 'competitorId', 'inputFilter');
        }

        return array('__isInitialized__', '' . "\0" . 'Application\\Entity\\Competitor' . "\0" . 'name', '' . "\0" . 'Application\\Entity\\Competitor' . "\0" . 'url', '' . "\0" . 'Application\\Entity\\Competitor' . "\0" . 'strengths', '' . "\0" . 'Application\\Entity\\Competitor' . "\0" . 'weaknesses', 'projects', '' . "\0" . 'Application\\Entity\\Competitor' . "\0" . 'competitorId', 'inputFilter');
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Competitor $proxy) {
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
    public function getUrl()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUrl', array());

        return parent::getUrl();
    }

    /**
     * {@inheritDoc}
     */
    public function getStrengths()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getStrengths', array());

        return parent::getStrengths();
    }

    /**
     * {@inheritDoc}
     */
    public function getWeaknesses()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getWeaknesses', array());

        return parent::getWeaknesses();
    }

    /**
     * {@inheritDoc}
     */
    public function getProjects()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getProjects', array());

        return parent::getProjects();
    }

    /**
     * {@inheritDoc}
     */
    public function getCompetitorId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getCompetitorId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCompetitorId', array());

        return parent::getCompetitorId();
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
    public function setUrl($url)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setUrl', array($url));

        return parent::setUrl($url);
    }

    /**
     * {@inheritDoc}
     */
    public function setStrengths($strengths)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setStrengths', array($strengths));

        return parent::setStrengths($strengths);
    }

    /**
     * {@inheritDoc}
     */
    public function setWeaknesses($weaknesses)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setWeaknesses', array($weaknesses));

        return parent::setWeaknesses($weaknesses);
    }

    /**
     * {@inheritDoc}
     */
    public function setProjects($projects)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setProjects', array($projects));

        return parent::setProjects($projects);
    }

    /**
     * {@inheritDoc}
     */
    public function setCompetitorId($competitorId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCompetitorId', array($competitorId));

        return parent::setCompetitorId($competitorId);
    }

    /**
     * {@inheritDoc}
     */
    public function populate($data = array (
))
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'populate', array($data));

        return parent::populate($data);
    }

    /**
     * {@inheritDoc}
     */
    public function getArrayCopy()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getArrayCopy', array());

        return parent::getArrayCopy();
    }

    /**
     * {@inheritDoc}
     */
    public function setInputFilter(\Zend\InputFilter\InputFilterInterface $inputFilter)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setInputFilter', array($inputFilter));

        return parent::setInputFilter($inputFilter);
    }

    /**
     * {@inheritDoc}
     */
    public function getInputFilter()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getInputFilter', array());

        return parent::getInputFilter();
    }

}
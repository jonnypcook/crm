<?php
namespace Product\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/** @ORM\Entity */
class Product
{
    /**
     * @var string
     *
     * @ORM\Column(name="model", type="string", length=100, nullable=false, unique=true)
     */
    private $model;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var float
     *
     * @ORM\Column(name="cpu", type="decimal", scale=2, nullable=false)
     */
    private $cpu;

    /**
     * @var float
     *
     * @ORM\Column(name="ppu", type="decimal", scale=2, nullable=false)
     */
    private $ppu;

    /**
     * @var float
     *
     * @ORM\Column(name="ibppu", type="decimal", scale=2, nullable=false)
     */
    private $ibppu;

    /**
     * @var float
     *
     * @ORM\Column(name="ppu_trial", type="decimal", scale=2, nullable=true)
     */
    private $ppuTrial;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=false)
     */
    private $active;

    /**
     * @var boolean
     *
     * @ORM\Column(name="eca", type="boolean", nullable=false)
     */
    private $eca;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="mcd", type="boolean", nullable=false)
     */
    private $mcd;

    /**
     * @var float
     *
     * @ORM\Column(name="pwr", type="decimal", scale=4, nullable=true)
     */
    private $pwr;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created; 
    

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Brand")
     * @ORM\JoinColumn(name="product_brand_id", referencedColumnName="product_brand_id", nullable=false)
     */
    private $brand; 
    

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Type")
     * @ORM\JoinColumn(name="product_type_id", referencedColumnName="product_type_id", nullable=false)
     */
    private $type; 
    
    
    /**
     * @var int
     *
     * @ORM\Column(name="sagepay", type="integer")
     */
    private $sagepay;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="attributes", type="text", nullable=true)
     */
    private $attributes;


    /**
     * @var integer
     *
     * @ORM\Column(name="product_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $productId;

	
    public function __construct()
	{
		$this->setCreated(new \DateTime());
        $this->setIbppu(0);
        $this->setActive(true);
        $this->setEca(false);
        $this->setMcd(false);
        $this->brand = new ArrayCollection();
        $this->type = new ArrayCollection();
	}
    
    public function getBrand() {
        return $this->brand;
    }

    public function getType() {
        return $this->type;
    }

    public function getModel() {
        return $this->model;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getCpu() {
        return $this->cpu;
    }

    public function getPpu() {
        return $this->ppu;
    }

    public function getIbppu() {
        return $this->ibppu;
    }

    public function getPpuTrial() {
        return $this->ppuTrial;
    }

    public function getActive() {
        return $this->active;
    }

    public function getEca() {
        return $this->eca;
    }

    public function getMcd() {
        return $this->mcd;
    }

    public function getPwr() {
        return $this->pwr;
    }

    public function getCreated() {
        return $this->created;
    }
    
    public function getSagepay() {
        return $this->sagepay;
    }

    
    public function getProductId() {
        return $this->productId;
    }

    public function setModel($model) {
        $this->model = $model;
        return $this;
    }

    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    public function setCpu($cpu) {
        $this->cpu = $cpu;
        return $this;
    }

    public function setPpu($ppu) {
        $this->ppu = $ppu;
        return $this;
    }

    public function setIbppu($ibppu) {
        $this->ibppu = $ibppu;
        return $this;
    }

    public function setPpuTrial($ppuTrial) {
        $this->ppuTrial = $ppuTrial;
        return $this;
    }

    public function setActive($active) {
        $this->active = $active;
        return $this;
    }
    
    public function setBrand($brand) {
        $this->brand = $brand;
        return $this;
    }

    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    public function setEca($eca) {
        $this->eca = $eca;
        return $this;
    }

    public function setMcd($mcd) {
        $this->mcd = $mcd;
        return $this;
    }

    public function setPwr($pwr) {
        $this->pwr = $pwr;
        return $this;
    }

    public function setCreated(\DateTime $created) {
        $this->created = $created;
        return $this;
    }

    public function setProductId($productId) {
        $this->productId = $productId;
        return $this;
    }

    public function getAttributes() {
        return $this->attributes;
    }

    public function setAttributes($attributes) {
        $this->attributes = $attributes;
        return $this;
    }
    
    public function setSagepay($sagepay) {
        $this->sagepay = $sagepay;
        return $this;
    }


}



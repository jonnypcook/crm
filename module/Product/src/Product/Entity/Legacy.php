<?php
namespace Product\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/** 
 * @ORM\Entity 
 * @ORM\Table(name="Legacy")
 */
class Legacy
{
    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantity", type="integer", nullable=false)
     */
    private $quantity;

    /**
     * @var float
     *
     * @ORM\Column(name="pwr_item", type="decimal", scale=4, nullable=false)
     */
    private $pwr_item;

    /**
     * @var float
     *
     * @ORM\Column(name="pwr_ballast", type="decimal", scale=4, nullable=false)
     */
    private $pwr_ballast;

    /**
     * @var boolean
     *
     * @ORM\Column(name="emergency", type="boolean", nullable=false)
     */
    private $emergency;

    /**
     * @var string
     *
     * @ORM\Column(name="dim_item", type="string", length=64, nullable=true)
     */
    private $dim_item;

    /**
     * @var string
     *
     * @ORM\Column(name="dim_unit", type="string", length=64, nullable=true)
     */
    private $dim_unit;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false)
     */
    private $created; 
    

    /**
     * @var \DateTime
     *
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="legacy_category_id", referencedColumnName="legacy_category_id", nullable=false)
     */
    private $category; 
    

    /**
     * @var string
     *
     * @ORM\Column(name="attributes", type="text", nullable=true)
     */
    private $attributes;


    /**
     * @var integer
     *
     * @ORM\Column(name="legacy_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $legacyId;

	
    public function __construct()
	{
		$this->setCreated(new \DateTime());
        $this->setQuantity(1);
        $this->setEmergency(false);

        $this->category = new ArrayCollection();
	}
    
    
    public function getDescription() {
        return $this->description;
    }


    public function getQuantity() {
        return $this->quantity;
    }

    public function getPwr_item() {
        return $this->pwr_item;
    }

    public function getPwr_ballast() {
        return $this->pwr_ballast;
    }

    public function getCreated() {
        return $this->created;
    }

    public function getCategory() {
        return $this->category;
    }

    public function getAttributes() {
        return $this->attributes;
    }
    
    public function getDim_item() {
        return $this->dim_item;
    }

    public function getDim_unit() {
        return $this->dim_unit;
    }
        
    public function getEmergency() {
        return $this->emergency;
    }

    public function getLegacyId() {
        return $this->legacyId;
    }

    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    public function setQuantity($quantity) {
        $this->quantity = $quantity;
        return $this;
    }

    public function setPwr_item($pwr_item) {
        $this->pwr_item = $pwr_item;
        return $this;
    }

    public function setPwr_ballast($pwr_ballast) {
        $this->pwr_ballast = $pwr_ballast;
        return $this;
    }

    public function setCreated(\DateTime $created) {
        $this->created = $created;
        return $this;
    }

    public function setCategory(\DateTime $category) {
        $this->category = $category;
        return $this;
    }

    public function setAttributes($attributes) {
        $this->attributes = $attributes;
        return $this;
    }
    
    public function setDim_item($dim_item) {
        $this->dim_item = $dim_item;
        return $this;
    }

    public function setDim_unit($dim_unit) {
        $this->dim_unit = $dim_unit;
        return $this;
    }

    
    public function setEmergency($emergency) {
        $this->emergency = $emergency;
        return $this;
    }
    
    public function setLegacyId($legacyId) {
        $this->legacyId = $legacyId;
        return $this;
    }

    
    public function getTotalPwr() {
        return ($this->getQuantity()*$this->getPwr_item()) + $this->getPwr_ballast();
    }

    
}



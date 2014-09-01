<?php
namespace Product\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/** 
 * @ORM\Entity 
 * @ORM\Table(name="Legacy_Category")
 */
class Category
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false, unique=true)
     */
    private $name;

    /**
     * @var float
     *
     * @ORM\Column(name="maintenance", type="float", precision=2, nullable=false)
     */
    private $maintenance;
    

     /**
     * @var integer
     *
     * @ORM\Column(name="legacy_category_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $categoryId;

	
    public function __construct()
	{
	}
    
    public function getName() {
        return $this->name;
    }

    public function getMaintenance() {
        return $this->maintenance;
    }

    public function getCategoryId() {
        return $this->categoryId;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function setMaintenance($maintenance) {
        $this->maintenance = $maintenance;
        return $this;
    }

}

<?php
namespace Product\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/** 
 * @ORM\Entity 
 * @ORM\Table(name="Product_Fixing")
 */
class Fixing
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false, unique=true)
     */
    private $name;

    
    /**
     * @var integer
     *
     * @ORM\Column(name="product_fixing_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $fixingId;

	
    public function __construct()
	{
	}

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function getFixingId() {
        return $this->fixingId;
    }

    public function setFixingId($fixingId) {
        $this->fixingId = $fixingId;
        return $this;
    }

}

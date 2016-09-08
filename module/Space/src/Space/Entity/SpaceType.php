<?php
namespace Space\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/** 
 * @ORM\Entity 
 * @ORM\Table(name="Space_Type")
 */
class SpaceType
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="space_type_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $typeId;

	
    public function __construct()
	{
	}
    
    public function getName() {
        return $this->name;
    }

    public function getTypeId() {
        return $this->typeId;
    }

    public function setTypeId($typeId) {
        $this->typeId = $typeId;
        return $this;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }


}

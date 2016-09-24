<?php
namespace Space\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/** 
 * @ORM\Entity 
 * @ORM\Table(name="Space_Grid")
 */
class Grid
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
     * @ORM\Column(name="space_grid_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $gridId;

	
    public function __construct()
	{
	}
    
    public function getName() {
        return $this->name;
    }

    public function getGridId() {
        return $this->gridId;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function setGridId($gridId) {
        $this->gridId = $gridId;
        return $this;
    }



}

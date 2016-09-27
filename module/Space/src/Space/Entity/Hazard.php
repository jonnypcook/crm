<?php
namespace Space\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/** 
 * @ORM\Entity 
 * @ORM\Table(name="Hazard")
 */
class Hazard
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
     * @ORM\Column(name="hazard_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $hazardId;

    /** 
     * @ORM\OneToMany(targetEntity="Space\Entity\SpaceHazard", mappedBy="hazard") 
     */
    protected $spaces; 
	
    public function __construct()
	{
        $this->spaces = new ArrayCollection();
	}
    
    public function getSpaces() {
        return $this->spaces;
    }

    public function setSpaces($spaces) {
        $this->spaces = $spaces;
        return $this;
    }

        
    public function getName() {
        return $this->name;
    }

    public function getHazardId() {
        return $this->hazardId;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function setHazardId($hazardId) {
        $this->hazardId = $hazardId;
        return $this;
    }

}

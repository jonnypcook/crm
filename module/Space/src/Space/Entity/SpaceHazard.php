<?php
namespace Space\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface; 

/** 
 * @ORM\Table(name="Space_Hazard")
 * @ORM\Entity 
 * @ORM\Entity(repositoryClass="Space\Repository\SpaceHazard")
 */
class SpaceHazard implements InputFilterAwareInterface
{
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false)
     */
    private $created; 

    /** 
     * @ORM\ManyToOne(targetEntity="Space\Entity\Space", inversedBy="hazards") 
     * @ORM\JoinColumn(name="space_id", referencedColumnName="space_id", nullable=true)* 
     */
    protected $space;

    /** 
     * @ORM\ManyToOne(targetEntity="Space\Entity\Hazard", inversedBy="spaces") 
     * @ORM\JoinColumn(name="hazard_id", referencedColumnName="hazard_id", nullable=true)* 
     */
    protected $hazard;

    /** 
     * @var string 
     * 
     * @ORM\Column(name="location", type="text", nullable=false ) 
     */ 
    private $location;
    
    public function getId() {
        return $this->id;
    }

    public function getCreated() {
        return $this->created;
    }

    public function getSpace() {
        return $this->space;
    }

    public function getHazard() {
        return $this->hazard;
    }

    public function getLocation() {
        return $this->location;
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function setCreated(\DateTime $created) {
        $this->created = $created;
        return $this;
    }

    public function setSpace($space) {
        $this->space = $space;
        return $this;
    }

    public function setHazard($hazard) {
        $this->hazard = $hazard;
        return $this;
    }

    public function setLocation($location) {
        $this->location = $location;
        return $this;
    }

                
    /**
     * Populate from an array.
     *
     * @param array $data
     */
    public function populate($data = array()) 
    {
        //print_r($data);die();
        foreach ($data as $name=>$value) {
            $fn = "set{$name}";
            try {
                $this->$fn($value);
            } catch (\Exception $ex) {

            }
        }
    }/**/

    public function __construct() {
		$this->setCreated(new \DateTime());
    }
    
    /**
     * Convert the object to an array.
     *
     * @return array
     */
    public function getArrayCopy() 
    {
        return get_object_vars($this);
    }
    
    
    protected $inputFilter;
    
    /**
     * set the input filter- only in forstructural and inheritance purposes
     * @param \Zend\InputFilter\InputFilterInterface $inputFilter
     * @throws \Exception
     */
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }
    
    
    /**
     * 
     * @return Zend\InputFilter\InputFilter
     */
    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
 
            $factory = new InputFactory();
            
            //example in google under search: "zf2 doctrine Album example"
            $this->inputFilter = $inputFilter;        
        }
 
        return $this->inputFilter;
    } 
    
    

}



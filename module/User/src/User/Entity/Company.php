<?php
namespace User\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface; 

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/** 
 * @ORM\Entity 
 * @ORM\Table(name="Company")
 */
class Company implements InputFilterAwareInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;


    /**
     * @var string
     *
     * @ORM\Column(name="displayName", type="string", length=100, nullable=false)
     */
    private $displayName;


    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $companyId;

	
    public function __construct()
	{
	}

    public function getName() {
        return $this->name;
    }

    public function getCompanyId() {
        return $this->companyId;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function setCompanyId($companyId) {
        $this->companyId = $companyId;
        return $this;
    }
    
    public function getDisplayName() {
        return $this->displayName;
    }

    public function setDisplayName($displayName) {
        $this->displayName = $displayName;
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
            
            $this->inputFilter = $inputFilter;        
        }
 
        return $this->inputFilter;
    } 
}

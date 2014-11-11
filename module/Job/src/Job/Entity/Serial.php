<?php
namespace Job\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface; 

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/** 
 * @ORM\Table(name="Serial")
 * @ORM\Entity 
 * @ORM\Entity(repositoryClass="Job\Repository\Serial")
 */
class Serial 
{
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created; 
    

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Project\Entity\Project", inversedBy="serials")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="project_id", nullable=true)
     */
    private $project; 
    
    
    /**
     * @var integer
     *
     * @ORM\Column(name="serial_id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $serialId;

	
    public function __construct()
	{
		$this->setCreated(new \DateTime());
        
        $this->project= new ArrayCollection();
	}
    
    public function getCreated() {
        return $this->created;
    }

    public function getProject() {
        return $this->project;
    }

    public function getSerialId() {
        return $this->serialId;
    }

    public function setCreated(\DateTime $created) {
        $this->created = $created;
        return $this;
    }

    public function setProject($project) {
        $this->project = $project;
        return $this;
    }

    public function setSerialId($serialId) {
        $this->serialId = $serialId;
        return $this;
    }


    

}



<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/** @ORM\Entity */
class Privilege
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     * @Annotation\Attributes({"type":"text"})
     * @Annotation\Options({"label":"Role Name:"})
     */
    private $name;


    /**
     * @var integer
     *
     * @ORM\Column(name="privilege_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
	 * @Annotation\Exclude()
     */
    private $privilegeId;

	
    /**
     * Set name
     *
     * @param string $name
     * @return Users
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    

    /**
     * Get privilegeId
     *
     * @return integer 
     */
    public function getPrivilegeId()
    {
        return $this->privilegeId;
    }
}

<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/** @ORM\Entity */
class Role
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
     * @ORM\Column(name="role_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
	 * @Annotation\Exclude()
     */
    private $roleId;

    
    /**
     * @var integer
     *
     * @ORM\ManyToMany(targetEntity="Privilege") 
     * @ORM\JoinTable(name="Role_Privilege", joinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="role_id")}, inverseJoinColumns={@ORM\JoinColumn(name="privilege_id", referencedColumnName="privilege_id")})
	 * @Annotation\Type("Zend\Form\Element\MultiSelect")
	 * @Annotation\Options({
	 * "label":"User Privilege:",
	 * "value_options":{ "0":"Select Privilege", "1":"Public", "2": "Member"}})
     */
    private $privileges;    
    
    public function __construct() {
        $this->privileges = new ArrayCollection();
    }
	
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
     * Get privileges
     *
     * @return ArrayCollection
     */
    public function getPrivileges()
    {
        return $this->privileges;
    }



    /**
     * Get roleId
     *
     * @return integer 
     */
    public function getRoleId()
    {
        return $this->roleId;
    }
}

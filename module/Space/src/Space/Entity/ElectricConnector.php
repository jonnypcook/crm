<?php
namespace Space\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/** 
 * @ORM\Entity 
 * @ORM\Table(name="Space_Electric_Connector")
 */
class ElectricConnector
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
     * @ORM\Column(name="space_electric_connector_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $electricConnectorId;

	
    public function __construct()
	{
	}
    
    public function getName() {
        return $this->name;
    }

    public function getElectricConnectorId() {
        return $this->electricConnectorId;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function setElectricConnectorId($electricConnectorId) {
        $this->electricConnectorId = $electricConnectorId;
        return $this;
    }



}

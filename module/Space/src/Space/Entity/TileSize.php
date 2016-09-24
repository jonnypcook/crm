<?php
namespace Space\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/** 
 * @ORM\Entity 
 * @ORM\Table(name="Space_Tile_Size")
 */
class TileSize
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
     * @ORM\Column(name="space_tile_size_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $tileSizeId;

	
    public function __construct()
	{
	}
    
    public function getName() {
        return $this->name;
    }

    public function getTileSizeId() {
        return $this->tileSizeId;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function setTileSizeId($tileSizeId) {
        $this->tileSizeId = $tileSizeId;
        return $this;
    }



}

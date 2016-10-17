<?php
namespace Space\Form;

use Zend\Form\Form;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;


class SpaceCreateForm extends Form implements \DoctrineModule\Persistence\ObjectManagerAwareInterface
{
    public function __construct(\Doctrine\ORM\EntityManager $em, $clientId)
    {
        $name = preg_replace('/^[\s\S]*[\\\]([a-z0-9_]+)$/i','$1',__CLASS__);
        // we want to ignore the name passed
        parent::__construct($name);

        $this->setObjectManager($em);

        $this->setHydrator(new DoctrineHydrator($this->getObjectManager(),'Space\Entity\Space'));


        $this->setAttribute('method', 'post');

        $this->add(array(
            'name' => 'name', // 'usr_name',
            'attributes' => array(
                'type'  => 'text',
                'data-original-title' => 'This is the unique name by which this space will be referenced',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
            ),
            'options' => array(
            ),
        ));

        $this->add(array(     
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',       
            'name' => 'building',
            'attributes' =>  array(
                'data-original-title' => 'The building to which the space belongs',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
                'data-placeholder' => "Choose a Building"
            ),
            'options' => array(
                'empty_option' => 'Please Select',
                'object_manager' => $this->getObjectManager(),
                'target_class'   => 'Client\Entity\Building',
                'property'       => 'name',
                'is_method' => true,
                'find_method' => array(
                    'name' => 'findByClientId',
                    'params' => array(
                        'client_id' => $clientId,
                    )
                )             
             ),
        ));    

        $this->add(array(     
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',       
            'name' => 'spaceType',
            'attributes' =>  array(
                'data-original-title' => 'the type of the space',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
                'data-placeholder' => "Choose a Space Type"
            ),
            'options' => array(
                'empty_option' => 'Please Select',
                'object_manager' => $this->getObjectManager(),
                'target_class'   => 'Space\Entity\SpaceType',
                'property'       => 'name',
             ),
        ));    

        $this->add(array(
            'name' => 'floor', // 'usr_name',
            'attributes' => array(
                'type'  => 'number',
                'min' => 0,
                'data-original-title' => 'The floor number of the building that the space is on',
                'data-trigger' => 'hover',
                'class' => 'span3  tooltips',
            ),
            'options' => array(
            ),
        ));

        $this->add(array(
            'name' => 'dimx', // 'usr_name',
            'attributes' => array(
                'type'  => 'number',
                'min' => 0,
                'data-original-title' => 'The width of the space',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
            ),
            'options' => array(
            ),
        ));


        $this->add(array(
            'name' => 'dimy', // 'usr_name',
            'attributes' => array(
                'type'  => 'number',
                'min' => 0,
                'data-original-title' => 'The depth of the space',
                'data-trigger' => 'hover',
                'class' => 'span12 tooltips',
            ),
            'options' => array(
            ),
        ));

         $this->add(array(
            'name' => 'dimh', // 'usr_name',
            'attributes' => array(
                'type'  => 'number',
                'min' => 0,
                'data-original-title' => 'The height of the space',
                'data-trigger' => 'hover',
                'class' => 'span12 tooltips',
            ),
            'options' => array(
            ),
        ));
        
         
         
         
        $this->add(array(     
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',       
            'name' => 'ceiling',
            'attributes' =>  array(
                'data-original-title' => 'the type of the ceiling',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
                'data-placeholder' => "Choose a Ceiling Type"
            ),
            'options' => array(
                'empty_option' => 'Please Select',
                'object_manager' => $this->getObjectManager(),
                'target_class'   => 'Space\Entity\Ceiling',
                'property'       => 'name',
             ),
        ));    
        
        $this->add(array(     
            'type' => 'Select',       
            'name' => 'metric',
            'attributes' =>  array(
                'data-original-title' => 'Is the tile sizing metric or imperial',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
                'data-placeholder' => "Choose a Ceiling Type"
            ),
            'options' => array (
                'value_options' => array (1 => 'Metric', 0=>'Imperial',)
            )

        ));
        
        $this->add(array(     
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',       
            'name' => 'tileSize',
            'attributes' =>  array(
                'data-original-title' => 'the size of the tile',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
                'data-placeholder' => "Choose a tile size"
            ),
            'options' => array(
                'empty_option' => 'Please Select',
                'object_manager' => $this->getObjectManager(),
                'target_class'   => 'Space\Entity\TileSize',
                'property'       => 'name',
             ),
        ));    
        
        $this->add(array(
            'name' => 'tileType', // 'usr_name',
            'attributes' => array(
                'type'  => 'text',
                'placeholder' => 'enter details',
                'data-original-title' => 'This is the type of tile used',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
            ),
            'options' => array(
            ),
        ));
        
        $this->add(array(
            'name' => 'voidDimension', // 'usr_name',
            'attributes' => array(
                'type'  => 'number',
                'min' => 0,
                'data-original-title' => 'The void dimension height in mm',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
            ),
            'options' => array(
            ),
        ));
         
        $this->add(array(     
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',       
            'name' => 'electricConnector',
            'attributes' =>  array(
                'data-original-title' => 'the type of the electric connector',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
                'data-placeholder' => "Choose a Electric Connector"
            ),
            'options' => array(
                'empty_option' => 'Please Select',
                'object_manager' => $this->getObjectManager(),
                'target_class'   => 'Space\Entity\ElectricConnector',
                'property'       => 'name',
             ),
        ));    
         
        $this->add(array(     
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',       
            'name' => 'grid',
            'attributes' =>  array(
                'data-original-title' => 'the type of the grid',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
                'data-placeholder' => "Choose a Grid Type"
            ),
            'options' => array(
                'empty_option' => 'Please Select',
                'object_manager' => $this->getObjectManager(),
                'target_class'   => 'Space\Entity\Grid',
                'property'       => 'name',
             ),
        ));  
        
        $this->add(array(
            'name' => 'luxLevel', // 'usr_name',
            'attributes' => array(
                'type'  => 'number',
                'min' => 0,
                'data-original-title' => 'The lux level of the space',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
            ),
            'options' => array(
            ),
        ));
        
    }
    
    protected $objectManager;
    
    public function setObjectManager(\Doctrine\Common\Persistence\ObjectManager $objectManager)
    {
    	$this->objectManager = $objectManager;
    }
    
    public function getObjectManager()
    {
    	return $this->objectManager;
    }
}
<?php
namespace Space\Form;

use Zend\Form\Form;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;


class SpaceHazardForm extends Form implements \DoctrineModule\Persistence\ObjectManagerAwareInterface
{
    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $name = preg_replace('/^[\s\S]*[\\\]([a-z0-9_]+)$/i','$1',__CLASS__);
        // we want to ignore the name passed
        parent::__construct($name);
        
        $this->setObjectManager($em);

        $this->setAttribute('method', 'post');
        
        
        $this->add(array(     
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',       
            'name' => 'hazard',
            'attributes' =>  array(
                'data-original-title' => 'Hazard category',
                'data-trigger' => 'hover',
                'class' => 'span12 tooltips',
            ),
            'options' => array(
                'empty_option' => 'Select Hazard',
                'object_manager' => $this->getObjectManager(),
                'target_class'   => 'Space\Entity\Hazard',
                'property'       => 'name',
                'is_method' => true,
                'find_method' => array(
                    'name' => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy' => array('name' => 'ASC')
                    )
                )                 
                
             ),
        ));
        

        $this->add(array(
            'name' => 'location', // 'label',
            'attributes' => array(
                'type'  => 'text',
                'data-original-title' => 'Enter description of location and details of hazard',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
                'placeholder' => 'Location'
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
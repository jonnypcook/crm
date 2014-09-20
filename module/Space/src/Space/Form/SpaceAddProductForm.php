<?php
namespace Space\Form;

use Zend\Form\Form;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;


class SpaceAddProductForm extends Form implements \DoctrineModule\Persistence\ObjectManagerAwareInterface
{
    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $name = preg_replace('/^[\s\S]*[\\\]([a-z0-9_]+)$/i','$1',__CLASS__);
        // we want to ignore the name passed
        parent::__construct($name);
        
        $this->setObjectManager($em);

        $this->setHydrator(new DoctrineHydrator($this->getObjectManager(),'Project\Entity\System'));

        
        $this->setAttribute('method', 'post');
        
        $pc = array();
        for ($i=0; $i<=18; $i++) {
            $pc[($i*5)] = ($i*5).'%';
        }

        $this->add(array(
            'name' => 'quantity', // 'usr_name',
            'attributes' => array(
                'type'  => 'text',
                'data-original-title' => 'Quantity of the product',
                'data-trigger' => 'hover',
                'class' => 'span6  tooltips',
            ),
            'options' => array(
            ),
        ));
        
        $this->add(array(
            'name' => 'hours', // 'usr_name',
            'attributes' => array(
                'type'  => 'text',
                'data-original-title' => 'Weekly hours of usage',
                'data-trigger' => 'hover',
                'class' => 'span8  tooltips',
            ),
            'options' => array(
            ),
        ));
        
        $this->add(array(
            'name' => 'ppu', // 'usr_name',
            'attributes' => array(
                'type'  => 'text',
                'data-original-title' => 'Price per unit of the product to the client',
                'data-trigger' => 'hover',
                'class' => 'span6  tooltips',
            ),
            'options' => array(
            ),
        ));
        
        $this->add(array(
            'name' => 'length', // 'usr_name',
            'attributes' => array(
                'type'  => 'text',
                'data-original-title' => 'Maximum required length of the fitting',
                'data-trigger' => 'hover',
                'class' => 'span6  tooltips',
            ),
            'options' => array(
            ),
        ));
        
        $this->add(array(
            'name' => 'ippu', // 'usr_name',
            'attributes' => array(
                'type'  => 'text',
                'data-original-title' => 'Installation cost per unit (if applicable)',
                'data-trigger' => 'hover',
                'class' => 'span6  tooltips',
            ),
            'options' => array(
            ),
        ));
        
        
        $this->add(array(
            'name' => 'legacyQuantity', // 'usr_name',
            'attributes' => array(
                'type'  => 'text',
                'data-original-title' => 'Quantity of the existing fittings',
                'data-trigger' => 'hover',
                'class' => 'span6  tooltips',
            ),
            'options' => array(
            ),
        ));
        
        $this->add(array(
            'name' => 'legacyWatts', // 'usr_name',
            'attributes' => array(
                'type'  => 'text',
                'data-original-title' => 'Power consumption of the existing fittings(in watts)',
                'data-trigger' => 'hover',
                'class' => 'span6  tooltips',
            ),
            'options' => array(
            ),
        ));
        
        $this->add(array(
            'name' => 'legacyMcpu', // 'usr_name',
            'attributes' => array(
                'type'  => 'text',
                'data-original-title' => 'Maintenance cost per unit for existing fittings',
                'data-trigger' => 'hover',
                'class' => 'span6  tooltips',
            ),
            'options' => array(
            ),
        ));
        
        $this->add(array(
            'name' => 'label', // 'label',
            'attributes' => array(
                'type'  => 'text',
                'data-original-title' => 'Additional information about the products in the space (maximum 512 characters)',
                'data-trigger' => 'hover',
                'class' => 'span12  tooltips',
            ),
            'options' => array(
            ),
        ));
        
        $this->add(array(     
            'type' => 'Select',       
            'name' => 'lux',
            'attributes' =>  array(
                'data-original-title' => 'Effect of the LUX sensor on this grouping of products',
                'data-trigger' => 'hover',
                'class' => 'span3  tooltips',
            ),
            'options' => array (
                'value_options' => $pc
            )

        ));
        
        $this->add(array(     
            'type' => 'Select',       
            'name' => 'occupancy',
            'attributes' =>  array(
                'data-original-title' => 'Effect of the occupancy sensor on this grouping of products',
                'data-trigger' => 'hover',
                'class' => 'span3  tooltips',
            ),
            'options' => array (
                'value_options' => $pc
            )

        ));
        
        
        $this->add(array(     
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',       
            'name' => 'product',
            'attributes' =>  array(
                'data-original-title' => 'The 8point3 product',
                'data-trigger' => 'hover',
                'class' => 'span12 chzn-select tooltips',
                //'data-placeholder' => "Choose a Building"
            ),
            'options' => array(
                'empty_option' => 'Please Select',
                'object_manager' => $this->getObjectManager(),
                'target_class'   => 'Product\Entity\Product',
                'property'       => 'model',
                'is_method' => true,
                'find_method' => array(
                    'name' => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy' => array('model' => 'ASC')
                    )
                )                 
                
             ),
        ));    
        
        
        $this->add(array(     
            'type' => 'DoctrineModule\Form\Element\ObjectSelect',       
            'name' => 'legacy',
            'attributes' =>  array(
                'data-original-title' => 'Legacy product',
                'data-trigger' => 'hover',
                'class' => 'span12 tooltips',
            ),
            'options' => array(
                'empty_option' => 'Please Select',
                'object_manager' => $this->getObjectManager(),
                'target_class'   => 'Product\Entity\Legacy',
                'property'       => 'model',
                'is_method' => true,
                'find_method' => array(
                    'name' => 'findBy',
                    'params' => array(
                        'criteria' => array(),
                        'orderBy' => array('description' => 'ASC')
                    )
                )                 
                
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
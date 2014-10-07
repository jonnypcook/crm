<?php
namespace Project\Form;

use Zend\Form\Form;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;


class DocumentWizardForm extends Form implements \DoctrineModule\Persistence\ObjectManagerAwareInterface
{
    public function __construct(\Doctrine\ORM\EntityManager $em, \Project\Entity\Project $project, array $config = array())
    {
        $name = preg_replace('/^[\s\S]*[\\\]([a-z0-9_]+)$/i','$1',__CLASS__);
        // we want to ignore the name passed
        parent::__construct($name);
        
        $this->setObjectManager($em);

        $this->setHydrator(new DoctrineHydrator($this->getObjectManager(),'Project\Entity\Project'));
        
        
        $this->setAttribute('method', 'post');
        
        foreach ($config as $name => $value) {
            switch ($name) {
                case 'user':
                    if ($value==1) {
                        $this->add(array(     
                            'type' => 'Select',       
                            'name' => 'user',
                            'type' => 'DoctrineModule\Form\Element\ObjectSelect',
                            'attributes' =>  array(
                                'data-content' => 'Selected company user',
                                'data-original-title' => 'User',
                                'data-trigger' => 'hover',
                                'class' => 'span6  popovers',
                                'data-placeholder' => "Choose a User",

                                //
                            ),
                            'options' => array(
                                'label' => 'User',
                                'object_manager' => $this->getObjectManager(),
                                'target_class'   => 'Application\Entity\User',
                                'order_by'=>'forename',
                                'label_generator' => function($targetEntity) {
                                    return $targetEntity->getForename() . ' ' . $targetEntity->getSurname();
                                },/**/
                                'is_method' => true,
                                'find_method' => array(
                                    'name' => 'findBy',
                                    'params' => array(
                                        'criteria' => array(),
                                        'orderBy' => array('forename' => 'ASC')
                                    )
                                ) 
                            ),
                        ));
                    }
                    break;
                case 'contact':
                    if ($value==1) {
                        $this->add(array(     
                            'type' => 'Select',       
                            'name' => 'contact',
                            'type' => 'DoctrineModule\Form\Element\ObjectSelect',
                            'attributes' =>  array(
                                'data-content' => 'Selected client contact',
                                'data-original-title' => 'Contact',
                                'data-trigger' => 'hover',
                                'class' => 'span6  popovers',
                                'data-placeholder' => "Choose a Contact",

                                //
                            ),
                            'options' => array(
                                'label' => 'Contact',
                                'object_manager' => $this->getObjectManager(),
                                'target_class'   => 'Contact\Entity\Contact',
                                'order_by'=>'forename',
                                'label_generator' => function($targetEntity) {
                                    return $targetEntity->getForename() . ' ' . $targetEntity->getSurname();
                                },/**/
                                'is_method' => true,
                                'find_method' => array(
                                    'name' => 'findByClientId',
                                    'params' => array(
                                        'client_id' => $project->getClient()->getClientId(),
                                    )
                                ) 
                            ),
                        ));
                    }
                    break;
                case 'autosave':
                    $this->add(array(     
                        'type' => 'checkbox',       
                        'name' => 'autosave',
                        'attributes' =>  array(
                            'data-content' => 'Do you want to auto save this document to the Google Docs repository?',
                            'data-original-title' => 'Auto Save Document',
                            'data-trigger' => 'hover',
                            'class' => 'span6  popovers',
                            'value' => ($value==1),
                        ),
                        'options' => array(
                            'label' => 'Auto Save',
                        ),
                    ));
                    break;
                case 'model':
                    
                    break;
            }
        }
        

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
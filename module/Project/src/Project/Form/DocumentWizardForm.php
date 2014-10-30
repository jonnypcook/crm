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
                case 'attachments':
                    if (!is_array($value)) {
                        continue;
                    }
                    
                    foreach ($value as $attachment=>$switch) {
                        switch ($attachment) {
                            case 'tac':
                                
                                $this->add(array(     
                                    'type' => 'checkbox',       
                                    'name' => 'AttachTAC',
                                    'attributes' =>  array(
                                        'data-content' => 'Add terms and conditions',
                                        'data-original-title' => 'Attachment',
                                        'data-trigger' => 'hover',
                                        'class' => 'span6  popovers',
                                        ($switch?'checked':'unchecked') => 'true'
                                    ),
                                    'options' => array(
                                        'label' => 'Attach Terms of Service',
                                    ),
                                ));
                                break;
                            case 'breakdown':
                                $this->add(array(     
                                    'type' => 'Checkbox',       
                                    'name' => 'AttachBreakdown',
                                    'attributes' =>  array(
                                        'data-content' => 'Add detailed cost breakdown',
                                        'data-original-title' => 'Attachment',
                                        'data-trigger' => 'hover',
                                        'class' => 'span6  popovers',
                                        ($switch?'checked':'unchecked') => 'true'
                                    ),
                                    'options' => array(
                                        'label' => 'Attach Cost Breakdown',
                                    ),
                                ));
                                break;
                            case 'model':
                                $this->add(array(     
                                    'type' => 'Checkbox',       
                                    'name' => 'AttachModel',
                                    'attributes' =>  array(
                                        'data-content' => 'Add detailed model forecast',
                                        'data-original-title' => 'Attachment',
                                        'data-trigger' => 'hover',
                                        'class' => 'span6  popovers',
                                        ($switch?'checked':'unchecked') => 'true'
                                    ),
                                    'options' => array(
                                        'label' => 'Attach Model Forecast',
                                    ),
                                ));
                                break;
                            case 'modelGraph':
                                $this->add(array(     
                                    'type' => 'Checkbox',       
                                    'name' => 'AttachModelGraph',
                                    'attributes' =>  array(
                                        'data-content' => 'Add Model Graph',
                                        'data-original-title' => 'Attachment',
                                        'data-trigger' => 'hover',
                                        'class' => 'span6  popovers',
                                        ($switch?'checked':'unchecked') => 'true'
                                    ),
                                    'options' => array(
                                        'label' => 'Attach Model Graph',
                                    ),
                                ));
                                break;
                            case 'survey':
                                $this->add(array(     
                                    'type' => 'Checkbox',       
                                    'name' => 'AttachSurvey',
                                    'attributes' =>  array(
                                        'data-content' => 'Add survey request form',
                                        'data-original-title' => 'Attachment',
                                        'data-trigger' => 'hover',
                                        'class' => 'span6  popovers',
                                        ($switch?'checked':'unchecked') => 'true'
                                    ),
                                    'options' => array(
                                        'label' => 'Attach Survey Request',
                                    ),
                                ));
                                break;
                            case 'quotation':
                                $this->add(array(     
                                    'type' => 'Checkbox',       
                                    'name' => 'AttachQuotation',
                                    'attributes' =>  array(
                                        'data-content' => 'Add quotation document',
                                        'data-original-title' => 'Attachment',
                                        'data-trigger' => 'hover',
                                        'class' => 'span6  popovers',
                                        ($switch?'checked':'unchecked') => 'true'
                                    ),
                                    'options' => array(
                                        'label' => 'Attach Quotation',
                                    ),
                                ));
                                break;
                        }
                    }
                    break;
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
                                    return $targetEntity->getTitle()->getDisplay() . ' ' . $targetEntity->getForename() . ' ' . $targetEntity->getSurname();
                                },/**/
                                'is_method' => true,
                                'find_method' => array(
                                    'name' => 'findByProjectId',
                                    'params' => array(
                                        'project_id' => $project->getProjectId(),
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
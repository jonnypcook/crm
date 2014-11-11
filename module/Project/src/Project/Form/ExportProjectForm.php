<?php
namespace Project\Form;

use Zend\Form\Form;


class ExportProjectForm extends Form 
{
    public function __construct()
    {
        $name = preg_replace('/^[\s\S]*[\\\]([a-z0-9_]+)$/i','$1',__CLASS__);
        // we want to ignore the name passed
        parent::__construct($name);
        
        
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name' => 'name', // 'usr_name',
            'attributes' => array(
                'type'  => 'text',
                'data-content' => 'This is the unique name by which this project will be referenced',
                'data-original-title' => 'Project Name',
                'data-trigger' => 'hover',
                'class' => 'span12  popovers',
            ),
            'options' => array(
            ),
        ));
        
    }
    
}
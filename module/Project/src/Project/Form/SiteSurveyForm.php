<?php
namespace Project\Form;

use Zend\Form\Form;


class SiteSurveyForm extends Form 
{
    public function __construct()
    {
        $name = preg_replace('/^[\s\S]*[\\\]([a-z0-9_]+)$/i','$1',__CLASS__);
        // we want to ignore the name passed
        parent::__construct($name);
        
        
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name' => 'SurveyDate', 
            'attributes' => array(
                'type'  => 'text',
                'data-original-title' => 'Date of survey',
                'data-placement' => 'left',
                'data-trigger' => 'hover',
                'data-mask' => '99/99/9999',
                'class' => 'tooltips ',
                //'readonly' => 'true',
            ),
            'options' => array(
            ),
        ));
        
        $this->add(array(
            'name' => 'GasReading',
            'attributes' => array(
                'data-original-title' => 'Gas reading',
                'type'  => 'text',
                'class' => 'span12  popovers',
            ),
            'options' => array(
            ),
        ));

        $this->add(array(
            'name' => 'ElectricReading',
            'attributes' => array(
                'data-original-title' => 'Electric reading',
                'type'  => 'text',
                'class' => 'span12  popovers',
            ),
            'options' => array(
            ),
        ));

        $this->add(array(
            'name' => 'VoltageReading',
            'attributes' => array(
                'data-original-title' => 'Voltage',
                'type'  => 'text',
                'class' => 'span12  popovers',
            ),
            'options' => array(
            ),
        ));

        
    }
    
}
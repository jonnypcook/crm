<?php
namespace Login\Form;

use Zend\Form\Form;

class LoginForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('login');
        $this->setAttribute('method', 'post');

        $this->add(array(
            'name' => 'username', // 'usr_name',
            'attributes' => array(
                'type'  => 'text',
                'placeholder' => 'Username'
            ),
            'options' => array(
            ),
        ));
        
        $this->add(array(
            'name' => 'password', // 'usr_password',
            'attributes' => array(
                'type'  => 'password',
                'placeholder' => 'Password'
            ),
            'options' => array(
            ),
        ));

        $this->add(array(
            'name' => 'rememberme',
			'type' => 'checkbox', // 'Zend\Form\Element\Checkbox',			
            'attributes'=> array (
                'checked'=>true
            ),
            'options' => array(
            ),
        ));	
    }
}
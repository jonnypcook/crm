<?php
namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

use Application\Controller\AuthController;

class UserController extends AuthController
{
    /**
     * main Dashboard action
     * @return \Zend\View\Model\ViewModel
     */
    public function profileAction()
    {
        $this->setCaption('User Profile');

        /*$this->getView()
                ->setVariable('info', $info)
                ->setVariable('activities', $activities)
                ->setVariable('user', $this->getUser())
                ->setVariable('formActivity', $formActivity);/**/
        
        return $this->getView();
    }
    
    
    public function passwordAction()
    {
        $this->setCaption('Change Password');

        /*$this->getView()
                ->setVariable('info', $info)
                ->setVariable('activities', $activities)
                ->setVariable('user', $this->getUser())
                ->setVariable('formActivity', $formActivity);/**/
        
        return $this->getView();
    }
    
    
    
}

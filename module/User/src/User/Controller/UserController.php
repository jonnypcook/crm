<?php
namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

use Application\Controller\AuthController;

class UserController extends AuthController
{
    
    public function onDispatch(MvcEvent $e) {
        return parent::onDispatch($e);
    }
    
    /**
     * main Dashboard action
     * @return \Zend\View\Model\ViewModel
     */
    public function profileAction()
    {
        $this->setCaption('User Profile');

        $this->getView()
                ->setVariable('user', $this->getUser())
        ;/**/
        
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
    
    
    public function grevokeAction() {
        try {
            if (!$this->request->isXmlHttpRequest()) {
                throw new \Exception('illegal request type');
            }
            
            $this->revokeGoogle();
            
            $data = array('err'=>false);
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    
}

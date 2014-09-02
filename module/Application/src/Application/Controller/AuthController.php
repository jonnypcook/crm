<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Entity\User;

use Zend\View\Model\ViewModel;

use Zend\Mvc\MvcEvent;

abstract class AuthController extends AbstractActionController
{
    /*
     * @var Application\Entity\User
     */
    private $user;
    
    
    protected $_view;
    
    public function __construct() {
        $this->_view = new ViewModel();
        
    }
    
    
    final public function getView() {
        return $this->_view;
    }
    

    
    public function onDispatch(MvcEvent $e) {
        $auth = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');
        if(!$auth->hasIdentity()) {
            return $this->redirect()->toRoute('login', array('controller' => 'doctrine', 'action' => 'index'));
        }
        $this->setUser($auth->getIdentity());

        $this->layout()->setVariable('flashMessages', $this->flashMessenger()->getMessages());
        
        return parent::onDispatch($e);
    }
    
    
    public function addTitle($title) {
        // Getting the view helper manager from the application service manager
        $renderer = $this->getServiceLocator()->get('Zend\View\Renderer\PhpRenderer');
        $renderer->headTitle($title);
    }
    
    public function setCaption($caption) {
        $this->layout()->setVariable('caption', $caption);
    }
    
    
    /**
     * set user
     * @param \Application\Entity\User $user
     */
    public function setUser(User $user) {
        $this->user = $user;
        $this->layout()->setVariable('user', $user);
    }
    
    /**
     * get user
     * @return Application\Entity\User
     */
    public function getUser() {
        return $this->user;
    }
    
    
    /**             
	 * @var Doctrine\ORM\EntityManager
	 */                
	protected $em;

	public function getEntityManager()
	{
		if (null === $this->em) {
			$this->em = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
		}
		return $this->em;
	}
    
    
    
}

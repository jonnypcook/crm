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
    /**
     *  @var \Google_Client
     */
    private $google;
    
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
     * @return \Application\Entity\User
     */
    public function getUser() {
        return $this->user;
    }
    
    
    /**
     * get the google oauth client
     * @param type $autoRefresh
     * @return \Google_Client
     */
    public function getGoogle($autoRefresh=true) {
        if (!($this->google instanceof \Google_Client)) {
            // grab local config
            $config = $this->getServiceLocator()->get('Config');
            
            $this->google = new \Google_Client();
            $this->google->setAccessToken($this->getUser()->getToken_access());
            $this->google->setClientId($config['openAuth2']['google']['clientId']);
            $this->google->setClientSecret($config['openAuth2']['google']['clientSecret']);
            $this->google->setAccessType($config['openAuth2']['google']['accessType']);
            $this->google->setRedirectUri($config['openAuth2']['google']['redirectUri']);
            $this->google->setScopes($config['openAuth2']['google']['scope']);
        }
        
        if ($autoRefresh) {
            if ($this->google->isAccessTokenExpired()) {
                try {
                    $this->google->refreshToken($this->getUser()->getToken_refresh());
                    $this->getUser()->setToken_access($this->google->getAccessToken());
                    $this->getEntityManager()->persist($this->getUser());
                    $this->getEntityManager()->flush();
                } catch (\Exception $e) {
                    // do nothing
                }
            }
        }
        
        return $this->google;
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

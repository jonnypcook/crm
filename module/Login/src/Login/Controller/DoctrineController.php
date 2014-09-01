<?php
namespace Login\Controller;

// Authentication with Remember Me
// http://samsonasik.wordpress.com/2012/10/23/zend-framework-2-create-login-authentication-using-authenticationservice-with-rememberme/

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Json\Json;

use Application\Entity\User; // only for the filters

use Login\Form\LoginForm;       // <-- Add this import
use Login\Form\LoginFilter;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

class DoctrineController extends AbstractActionController
{
    public function indexAction()
    {
        $em = $this->getEntityManager();
		$auth = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');

		if ($auth->hasIdentity()) {
			// Identity exists; get it
            return $this->redirect()->toRoute('home');
        }
        
        $form = new LoginForm();
		$messages = null;

		$request = $this->getRequest();
        if ($request->isPost()) {
            //- $authFormFilters = new User(); // we use the Entity for the filters
			// TODO fix the filters
            //- $form->setInputFilter($authFormFilters->getInputFilter());

			// Filters have been fixed
			$form->setInputFilter(new LoginFilter($this->getServiceLocator()));
            $form->setData($request->getPost());
			// echo "<h1>I am here1</h1>";
            if ($form->isValid()) {
				$data = $form->getData();			
				// $data = $this->getRequest()->getPost();

				// If you used another name for the authentication service, change it here
				// it simply returns the Doctrine Auth. This is all it does. lets first create the connection to the DB and the Entity
				$authService = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');		
				// Do the same you did for the ordinar Zend AuthService	
				$adapter = $authService->getAdapter();
				$adapter->setIdentityValue($data['username']); //$data['usr_name']
				$adapter->setCredentialValue($data['password']); // $data['usr_password']
				$authResult = $authService->authenticate();

                if ($authResult->isValid()) {
					$identity = $authResult->getIdentity();
					$authService->getStorage()->write($identity);
					$time = 1209600; // 14 days 1209600/3600 = 336 hours => 336/24 = 14 days

                    if ($data['rememberme']) {
						$sessionManager = new \Zend\Session\SessionManager();
						$sessionManager->rememberMe($time);
					}

                    $this->AuditPlugin()->audit(1, $identity->getUserId());
                    
					return $this->redirect()->toRoute('home');
				}
				foreach ($authResult->getMessages() as $message) {
					$messages .= "$message\n"; 
				}	

			} else {
                //print_r($form->getMessages());
                //die('nv');
            }
		}
		return new ViewModel(array(
			'error' => 'Your authentication credentials are not valid',
			'form'	=> $form,
			'messages' => $messages,
		));
    }

    public function logoutAction()
	{
		// in the controller
		// $auth = new AuthenticationService();
		$auth = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');

		// @todo Set up the auth adapter, $authAdapter
		if ($auth->hasIdentity()) {
			$identity = $auth->getIdentity();
            $this->AuditPlugin()->audit(2, $identity->getUserId());
		}
		$auth->clearIdentity();
		$sessionManager = new \Zend\Session\SessionManager();
		$sessionManager->forgetMe();

        return $this->redirect()->toRoute('login');

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
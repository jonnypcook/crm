<?php
namespace Product\Controller;

// Authentication with Remember Me
// http://samsonasik.wordpress.com/2012/10/23/zend-framework-2-create-login-authentication-using-authenticationservice-with-rememberme/

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Json\Json;

use Application\Entity\User; 
use Application\Controller\AuthController;


class LegacyItemController extends AuthController
{
    
    public function indexAction()
    {
        $this->setCaption('Products');
        $id = (int) $this->params()->fromRoute('id', 0);
		return new ViewModel(array(
		));
    }

    public function editAction() {
    	return new ViewModel(array(
			'error' => 'Your authentication credentials are not valid',
			'form'	=> $form,
			'messages' => $messages,
		));
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
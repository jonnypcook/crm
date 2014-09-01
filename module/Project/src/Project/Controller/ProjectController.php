<?php
namespace Project\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Json\Json;

use Application\Entity\User; 
use Application\Controller\AuthController;


class ProjectController extends AuthController
{
    
    public function indexAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $this->setCaption('Project #'.$id);
		return new ViewModel(array(
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
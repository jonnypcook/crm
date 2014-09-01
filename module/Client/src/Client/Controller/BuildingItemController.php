<?php
namespace Client\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Json\Json;

use Application\Controller\AuthController;

use Zend\Mvc\MvcEvent;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;

use Zend\View\Model\JsonModel;
use Zend\Paginator\Paginator;


class BuildingitemController extends ClientSpecificController
{
    
    public function onDispatch(MvcEvent $e) {
        return parent::onDispatch($e);
    }
    
    public function indexAction()
    {
        die('roger roger');
        $this->setCaption('Building Management');

        $buildings = $this->getEntityManager()->getRepository('Client\Entity\Building')->findByClientId($this->getClient()->getclientId());
        
        $this->getView()->setVariable('buildings', $buildings);
        
        return $this->getView();
    }
    
}
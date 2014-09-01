<?php
namespace Client\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Json\Json;

use Application\Entity\User; 
use Application\Controller\AuthController;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;

use Zend\View\Model\JsonModel;
use Zend\Paginator\Paginator;

class ClientController extends AuthController
{
    
    public function indexAction()
    {
        $this->setCaption('Clients');
        $id = (int) $this->params()->fromRoute('id', 0);
		return new ViewModel(array(
		));
    }
    
    public function listAction() {
        $em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');

        if (!$this->request->isXmlHttpRequest()) {
            throw new \Exception('illegal request type');
        }
        
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder
            ->select('c')
            ->from('Client\Entity\Client', 'c')
            ->innerJoin('c.user', 'u');
        
        /* 
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */
        $keyword = $this->params()->fromQuery('sSearch','');
        $keyword = trim($keyword);
        if (!empty($keyword)) {
            if (preg_match('/^[\d]+$/', trim($keyword))) {
                $queryBuilder->andWhere('c.clientId LIKE :cid')
                ->setParameter('cid', '%'.$keyword.'%');
            } else {
                $queryBuilder->andWhere('c.name LIKE :name')
                ->setParameter('name', '%'.trim(preg_replace('/[*]+/','%',$keyword),'%').'%');
            }
        }        
        

        /*
         * Ordering
         */
        $aColumns = array('c.name','u.forename','c.projects','c.jobs','c.completed','c.created', 'c.clientId');
        $orderByP = $this->params()->fromQuery('iSortCol_0',false);
        $orderBy = array();
        if ($orderByP!==false)
        {
            for ( $i=0 ; $i<intval($this->params()->fromQuery('iSortingCols',0)) ; $i++ )
            {
                $j = $this->params()->fromQuery('iSortCol_'.$i);

                if ( $this->params()->fromQuery('bSortable_'.$j, false) == "true" )
                {
                    $dir = $this->params()->fromQuery('sSortDir_'.$i,'ASC');
                    if (is_array($aColumns[$j])) {
                        foreach ($aColumns[$j] as $ac) {
                            $orderBy[] = $ac." ".$dir;
                        }
                    } else {
                        $orderBy[] = $aColumns[$j]." ".($dir);
                    }
                }
            }

        }  
        if (empty($orderBy)) {
            $orderBy[] = 'c.name ASC';
        } 
        
        foreach ($orderBy as $ob) {
            $queryBuilder->add('orderBy', $ob);
        }
        
        /**/  
        
        // Create the paginator itself
        $paginator = new Paginator(
            new DoctrinePaginator(new ORMPaginator($queryBuilder))
        );

        $length = $this->params()->fromQuery('iDisplayLength', 10);
        $start = $this->params()->fromQuery('iDisplayStart', 1);
        $start = (floor($start / $length)+1);
        
        
        $paginator
            ->setCurrentPageNumber($start)
            ->setItemCountPerPage($length);
        
        $data = array(
            "sEcho" => intval($this->params()->fromQuery('sEcho', false)),
            "iTotalDisplayRecords" => $paginator->getTotalItemCount(),
            "iTotalRecords" => $paginator->getcurrentItemCount(),
            "aaData" => array()
        );/**/

        
        foreach ($paginator as $page) {
            //$url = $this->url()->fromRoute('client',array('id'=>$page->getclientId()));
            $data['aaData'][] = array (
                '<a href="javascript:" class="action-client-edit"  pid="'.$page->getclientId().'">'.$page->getName().'</a>',
                $page->getUser()->getHandle(),
                'n/a',
                'n/a',
                $page->getCreated()->format('d/m/Y H:i'),
                str_pad($page->getClientId(), 5, "0", STR_PAD_LEFT),
                '<button class="btn btn-primary action-client-edit" pid="'.$page->getclientId().'" ><i class="icon-pencil"></i></button>',
            );
        }
        
        return new JsonModel($data);/**/
    }    
    

}
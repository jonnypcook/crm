<?php
namespace Product\Controller;

// Authentication with Remember Me
// http://samsonasik.wordpress.com/2012/10/23/zend-framework-2-create-login-authentication-using-authenticationservice-with-rememberme/

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Json\Json;

use Application\Entity\User; 
use Application\Controller\AuthController;

use Product\Entity\Product;
use Product\Entity\Type;
use Product\Entity\Brand;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;

use Zend\View\Model\JsonModel;
use Zend\Paginator\Paginator;

class ProductController extends AuthController
{
    public function catalogAction()
    {
        $this->setCaption('Product Catalog');
		return new ViewModel(array());
    }
    
    public function listAction() {
        $em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');

        if (!$this->request->isXmlHttpRequest()) {
            throw new \Exception('illegal request type');
        }
        
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder
            ->select('p')
            ->from('Product\Entity\Product', 'p')
            ->innerJoin('p.brand', 'b')
            ->innerJoin('p.type', 't');
        
        /* 
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */
        $keyword = $this->params()->fromQuery('sSearch','');
        $keyword = trim($keyword);
        if (!empty($keyword)) {
            $queryBuilder->andWhere('p.model LIKE :model')
                ->setParameter('model', '%'.trim(preg_replace('/[*]+/','%',$keyword),'%').'%');
        }        
        

        /*
         * Ordering
         */
        $aColumns = array('p.model','p.description','b.name','t.name','p.ppu','p.eca');
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
            $orderBy[] = 'p.model ASC';
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
            $url = $this->url()->fromRoute('product',array('id'=>$page->getproductId()));
            $data['aaData'][] = array (
                '<a href="'.$url.'">'.$page->getModel().'</a>',
                $page->getDescription(),
                $page->getBrand()->getName(),
                $page->getType()->getName(),
                number_format($page->getPPU(),2),
                '<button class="btn btn-'.($page->getECA()?'success':'danger').'"><i class="icon-'.($page->getECA()?'ok':'remove').'"></i></button>',
                '<button class="btn btn-primary action-project-edit" pid="'.$page->getproductId().'" ><i class="icon-pencil"></i></button>',
            );
        }
        
        return new JsonModel($data);/**/
    }
    
    
    
}
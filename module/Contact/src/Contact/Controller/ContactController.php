<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Contact\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\User,    Application\Entity\Address,    Application\Entity\Projects;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;


class ContactController extends \Application\Controller\AuthController
{
    
    public function indexAction()
    {
        $this->setCaption('Contact Management');

        
//        $form = new \Contact\Form\ContactForm($this->getEntityManager(), $this->getClient()->getclientId());
//        $form->setAttribute('action', '/client-'.$this->getClient()->getClientId().'/contact-%c/'); // set URI to current page
        
//        $formAddr = new \Contact\Form\AddressForm($this->getEntityManager());
//        $formAddr->setAttribute('action', '/client-'.$this->getClient()->getClientId().'/addressadd/'); // set URI to current page
//        $formAddr->setAttribute('class', 'form-horizontal');

        //$this->getView()->setVariable('contacts', $contacts);
        
        
        return $this->getView();        
    }
    
    public function listAction() {
        try {
            $data = array();
            if (!$this->request->isXmlHttpRequest()) {
                throw new \Exception('illegal request type');
            }
            
            $em = $this->getEntityManager();
            $length = $this->params()->fromQuery('iDisplayLength', 10);
            $start = $this->params()->fromQuery('iDisplayStart', 1);
            $keyword = $this->params()->fromQuery('sSearch','');
            $params = array(
                'keyword'=>trim($keyword),
                'orderBy'=>array()
            );

            $orderBy = array(
                0=>'title',
                1=>'forename',
                2=>'surname',
                3=>'position',
                4=>'telephone',
                5=>'email',
            );
            for ( $i=0 ; $i<intval($this->params()->fromQuery('iSortingCols',0)) ; $i++ )
            {
                $j = $this->params()->fromQuery('iSortCol_'.$i);
                if ( $this->params()->fromQuery('bSortable_'.$j, false) == "true" )
                {
                    $dir = $this->params()->fromQuery('sSortDir_'.$i,'ASC');
                    if (isset($orderBy[$j])) {
                        $params['orderBy'][$orderBy[$j]]=$dir;
                    }
                }/**/
            }


            $paginator = $em->getRepository('Contact\Entity\Contact')->findPaginateByCompanyId($this->getUser()->getCompany()->getCompanyId(), $length, $start, $params);

            $data = array(
                "sEcho" => intval($this->params()->fromQuery('sEcho', false)),
                "iTotalDisplayRecords" => $paginator->getTotalItemCount(),
                "iTotalRecords" => $paginator->getcurrentItemCount(),
                "aaData" => array()
            );/**/


            foreach ($paginator as $page) {
                //$url = $this->url()->fromRoute('client',array('id'=>$page->getclientId()));
                $data['aaData'][] = array (
                    !empty($page->getTitle())?(($page->getTitle()->getTitleId()==12)?' ':$page->getTitle()->getName()):' ',
                    $page->getForename(),
                    $page->getSurname(),
                    $page->getPosition(),
                    $page->getTelephone1(),
                    $page->getEmail(),
                );
            }    

        } catch (\Exception $ex) {
            $data = array('error'=>true, 'info'=>$ex->getMessage());
        }
        
        return new JsonModel($data);/**/
    }
    
    
    
    
    
}

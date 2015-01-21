<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Job\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\User,    Application\Entity\Address,    Application\Entity\Projects;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

class JobitemController extends JobSpecificController
{
    
    public function indexAction()
    {
        $this->setCaption('Job Dashboard');
        
        $em = $this->getEntityManager();
        $discount = (1-$this->getProject()->getMcd());
        $query = $em->createQuery('SELECT p.productId, p.model, p.eca, pt.service, pt.name AS productType, pt.typeId, s.ppu, '
                . 'SUM(s.quantity) AS quantity, '
                . 'SUM(ROUND((s.ppu * '.$discount.'),2) * s.quantity) AS priceMCD, '
                . 'SUM(s.cpu*s.quantity) AS cost, '
                . 'SUM(s.ppu*s.quantity) AS price '
                . 'FROM Space\Entity\System s '
                . 'JOIN s.space sp '
                . 'JOIN s.product p '
                . 'JOIN p.type pt '
                . 'WHERE sp.project='.$this->getProject()->getProjectId().' '
                . 'GROUP BY s.product');
        $system = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        $query = $em->createQuery('SELECT count(d) '
                . 'FROM Project\Entity\DocumentList d '
                . 'WHERE '
                . 'd.project='.$this->getProject()->getProjectId().' AND '
                . 'd.category IN (1, 2, 3)'
                );
        $proposals = $query->getSingleScalarResult();
        
        $query = $em->createQuery('SELECT count(d) '
                . 'FROM Job\Entity\Dispatch d '
                . 'WHERE '
                . 'd.project='.$this->getProject()->getProjectId().' AND '
                . 'd.revoked = false'
                );
        $dispatchNotes = $query->getSingleScalarResult();
        
        $audit = $em->getRepository('Application\Entity\Audit')->findByProjectId($this->getProject()->getProjectId(), true, array(
            'max' => 8,
            'auto'=> true,
        ));
        
        $activities = $em->getRepository('Application\Entity\Activity')->findByProjectId($this->getProject()->getProjectId(), true, array(
            'max' => 8,
            'auto'=> true,
        ));

        $query = $em->createQuery('SELECT count(s) FROM Job\Entity\Serial s WHERE s.project ='.$this->getProject()->getProjectId());
        $serialCount = $query->getSingleScalarResult();
        
        $formActivity = new \Application\Form\ActivityAddForm($em, array(
            'projectId'=>$this->getProject()->getProjectId(),
        ));
        
        $formActivity
                ->setAttribute('action', '/activity/add/')
                ->setAttribute('class', 'form-nomargin');
        
        $contacts = $this->getProject()->getContacts();

        $payback = $this->getModelService()->payback($this->getProject());
        
        $this->getView()
                ->setVariable('dispatchNotes', $dispatchNotes)
                ->setVariable('serialCount', $serialCount)
                ->setVariable('contacts', $contacts)
                ->setVariable('proposals', $proposals)
                ->setVariable('formActivity', $formActivity)
                ->setVariable('figures', $payback['figures'])
                ->setVariable('user', $this->getUser())
                ->setVariable('audit', $audit)
                ->setVariable('activities', $activities)
                ->setVariable('system', $system);
        
		return $this->getView();
        
    }
    
    
    public function serialsAction()
    {
        $this->setCaption('Serial Management');
        

        $em = $this->getEntityManager();
        $form = new \Job\Form\SerialForm($em, $this->getProject());
        $form->setAttribute('class', 'form-horizontal');
        $form->setAttribute('action', '/client-'.$this->getProject()->getClient()->getClientId().'/job-'.$this->getProject()->getProjectId().'/serialadd/');
        
        
        $this->getView()
            ->setVariable('form', $form)
        ;
        
		return $this->getView();
    }
    
    public function seriallistAction () {
        $em = $this->getEntityManager();
        $length = $this->params()->fromQuery('iDisplayLength', 10);
        $start = $this->params()->fromQuery('iDisplayStart', 1);
        $keyword = $this->params()->fromQuery('sSearch','');
        $params = array(
            'keyword'=>trim($keyword),
            'orderBy'=>array()
        );
        
        $orderBy = array(
            0=>'serialId',
            1=>'productId',
            2=>'spaceId'
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
        
        $paginator = $em->getRepository('Job\Entity\Serial')->findPaginateByProjectId($this->getProject()->getProjectId(), $length, $start, $params);

        $data = array(
            "sEcho" => intval($this->params()->fromQuery('sEcho', false)),
            "iTotalDisplayRecords" => $paginator->getTotalItemCount(),
            "iTotalRecords" => $paginator->getcurrentItemCount(),
            "aaData" => array()
        );/**/

        foreach ($paginator as $page) {
            $data['aaData'][] = array (
                str_pad($page->getSerialId(), 8, "0", STR_PAD_LEFT),
                !empty($page->getSystem())?$page->getSystem()->getProduct()->getModel():'Not specified',
                !empty($page->getSystem())?$page->getSystem()->getSpace()->getName():'Not specified',
                !empty($page->getSystem())?'Linked':'Not Linked',
                $page->getCreated()->format('d/m/Y H:i'),
            );
        } 
        
        return new JsonModel($data);/**/        
    }
    
    function serialAddAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            $post = $this->params()->fromPost();
            $em = $this->getEntityManager();
            $form = new \Job\Form\SerialForm($em, $this->getProject());
            $form->setInputFilter(new \Job\Filter\SerialFilter());
            $form->setData($post);
            if ($form->isValid()) {
                $serialStart = $form->get('serialStart')->getValue();
                $serialEnd = $serialStart + ($form->get('range')->getValue()-1);
                $query = $em->createQuery('SELECT count(s) FROM Job\Entity\Serial s WHERE s.serialId >='.$serialStart.' AND s.serialId <= '.$serialEnd);
                $serialCount = $query->getSingleScalarResult();
                if ($serialCount>0) {
                    throw new \Exception($serialCount.' of the serials in the specified range are already assigned to projects');
                }
                
                if (!empty($post['systemId'])) {
                    $system = $this->getEntityManager()->find('Space\Entity\System', $post['systemId']);
                    if (!($system instanceof \Space\Entity\System)) {
                        throw \Exception('System is invalid');
                    }
                } else {
                    $space = null;
                }
                
                
                for ($i=$serialStart; $i<=$serialEnd; $i++) {
                    $serial = new \Job\Entity\Serial();
                    $serial
                            ->setSerialId($i)
                            ->setProject($this->getProject())
                            ->setSystem($system);
                    $em->persist($serial);
                    
                }
                
                $em->flush();
                
                $data = array('err'=>false);
                $this->AuditPlugin()->auditProject(250, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId(),array('data'=>array($serialStart, $form->get('range')->getValue())));
            } else {
                $data = array('err'=>true, 'info'=>$form->getMessages());
            }
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    
     /**
     * Add note to project
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function addNoteAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            
            $post = $this->getRequest()->getPost();
            $note = $post['note'];
            $errs = array();
            if (empty($note)) {
                $errs['note'] = array('Note cannot be empty');
            }
            
            if (!empty($errs)) {
                return new JsonModel(array('err'=>true, 'info'=>$errs));
            }
            
            $notes = $this->getProject()->getNotes();
            $notes = json_decode($notes, true);
            if (empty($notes)) {
                $notes = array();
            }
            
            $noteIdx = time();
            $notes[$noteIdx] = $note;
            $noteCnt = count($notes);
            $notes = json_encode($notes);
            
            $this->getProject()->setNotes($notes);
            $this->getEntityManager()->persist($this->getProject());
            $this->getEntityManager()->flush();
            
            if ($noteCnt==1) {
                $this->flashMessenger()->addMessage(array('The note has been added successfully to trial', 'Success!'));
            } 

            $data = array('err'=>false, 'cnt'=>$noteCnt, 'id'=>$noteIdx);
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    /**
     * Delete note from space
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function deleteNoteAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            $post = $this->getRequest()->getPost();
            $noteId = $post['nid'];
            
            $errs = array();
            if (empty($noteId)) {
                throw new \Exception('note identifier not found');
            }
            
            $notes = $this->getProject()->getNotes();
            $notes = json_decode($notes, true);
            
            if (!empty($notes[$noteId])) {
                unset($notes[$noteId]);
                $notes = json_encode($notes);
                $this->getProject()->setNotes($notes);
                $this->getEntityManager()->persist($this->getProject());
                $this->getEntityManager()->flush();
            }
            
            $data = array('err'=>false);
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    public function telemetryAction()
    {
        $this->setCaption('Trial Telemetry and Control Management');
        
        $em = $this->getEntityManager();
        
		return $this->getView();
    }
    
    function collaboratorsAction() {
        $saveRequest = ($this->getRequest()->isXmlHttpRequest());
        
        $form = new \Project\Form\CollaboratorsForm($this->getEntityManager());
        $form
            ->setAttribute('class', 'form-horizontal')
            ->setAttribute('action', '/client-'.$this->getProject()->getClient()->getClientId().'/job-'.$this->getProject()->getProjectId().'/collaborators/');

        $form->bind($this->getProject());
        $form->setBindOnValidate(true);        
        
        if ($saveRequest) {
            try {
                if (!$this->getRequest()->isPost()) {
                    throw new \Exception('illegal method');
                }
                
                $post = $this->params()->fromPost();
                if (empty($post['collaborators'])) {
                    $post['collaborators'] = array();
                }
                
                $hydrator = new DoctrineHydrator($this->getEntityManager(),'Project\Entity\Project');
                $hydrator->hydrate($post, $this->getProject());

                $this->getEntityManager()->persist($this->getProject());
                $this->getEntityManager()->flush();
                
                $data = array('err'=>false);
            } catch (\Exception $ex) {
                $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
            }

            return new JsonModel(empty($data)?array('err'=>true):$data);/**/
        } else {
            $this->setCaption('Collaborators');


            $this->getView()
                    ->setVariable('form', $form)
                    ;

            return $this->getView();
        }
        
    }
    
    public function setupAction()
    {
        $saveRequest = ($this->getRequest()->isXmlHttpRequest());
        
        $this->setCaption('Job Configuration');
        
        return $this->getView();
    }
    
    public function systemAction() {
        $this->setCaption('System Setup');
        $breakdown = $this->getModelService()->spaceBreakdown($this->getProject());

        $this->getView()
                ->setVariable('breakdown', $breakdown);/**/
        
		return $this->getView();
    }
    
    public function modelAction()
    {
        $this->setCaption('Project Model');
        $service = $this->getModelService()->payback($this->getProject());
        
        //echo '<pre>', print_r($service, true), '</pre>';        die('STOP');
        $this->getView()
            ->setVariable('figures', $service['figures'])
            ->setVariable('forecast', $service['forecast']);
        
		return $this->getView();
    }

    public function forecastAction()
    {
        $this->setCaption('Project System Forecast');
        $service = $this->getModelService()->payback($this->getProject());
        
        $this->getView()
            ->setVariable('figures', $service['figures'])
            ->setVariable('forecast', $service['forecast']);
        
		return $this->getView();
    }

    public function breakdownAction()
    {
        $this->setCaption('Project System Forecast');
        $breakdown = $this->getModelService()->spaceBreakdown($this->getProject());
        
        $this->getView()
            ->setVariable('breakdown', $breakdown);
        
		return $this->getView();
    }
    
    
    public function deliverynoteAction()
    {
        $this->setCaption('Delivery Notes');
        

        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT SUM(dp.quantity) AS Quantity, p.productId '
                . 'FROM Job\Entity\DispatchProduct dp '
                . 'JOIN dp.dispatch d '
                . 'JOIN dp.product p '
                . 'WHERE d.project = '.$this->getProject()->getProjectId().' '
                . 'GROUP BY p.productId'
                );
        $existingConf = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        $existing = array();
        foreach ($existingConf as $prodQuantity) {
            $existing[$prodQuantity['productId']] = $prodQuantity['Quantity'];
        }
        
        $breakdown = $this->getModelService()->billitems($this->getProject(), array('products'=>true));
        //$this->debug()->dump($breakdown);

        $form = new \Job\Form\DeliveryNoteForm($em, $this->getProject());
        $form
            ->setAttribute('class', 'form-horizontal')
            ->setAttribute('action', '/client-'.$this->getProject()->getClient()->getClientId().'/project-'.$this->getProject()->getProjectId().'/document/deliverynotegenerate/');
        
        $this->getView()
                ->setVariable('existing', $existing)
                ->setVariable('breakdown', $breakdown)
                ->setVariable('form', $form)
                ;
		return $this->getView();
    }
    
    public function deliverynotelistAction() {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            //throw new \Exception('illegal request format');
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
            0=>'id',
            1=>'postcode',
            2=>'reference',
            3=>'created',
            4=>'sent',
            5=>'owner'
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

        $paginator = $em->getRepository('Job\Entity\Dispatch')->findPaginateByProjectId($this->getProject()->getprojectId(), $length, $start, $params);

        $data = array(
            "sEcho" => intval($this->params()->fromQuery('sEcho', false)),
            "iTotalDisplayRecords" => $paginator->getTotalItemCount(),
            "iTotalRecords" => $paginator->getcurrentItemCount(),
            "aaData" => array()
        );/**/

        foreach ($paginator as $page) {
            $data['aaData'][] = array (
                str_pad($page->getDispatchId(), 5, "0", STR_PAD_LEFT),
                $page->getAddress()->assemble(', '),
                $page->getReference(),
                $page->getCreated()->format('d/m/Y H:i'),
                $page->getSent()->format('d/m/Y'),
                $page->getUser()->getForename().' '.$page->getUser()->getSurname(),
                 '<button class="btn btn-primary action-download" data-dispatchId="'.$page->getDispatchId().'" ><i class="icon-download-alt"></i></button>',
            );
        } 

        
        
        return new JsonModel($data);/**/
    }
    
    
    public function documentAction()
    {
        $this->setCaption('Document Generator');
        $bitwise = '(BIT_AND(d.compatibility, 32)=32)';
        $query = $this->getEntityManager()->createQuery('SELECT d.documentCategoryId, d.name, d.description, d.config, d.partial, d.grouping FROM Project\Entity\DocumentCategory d WHERE d.active = true AND '.$bitwise.' ORDER BY d.grouping');
        $documents = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        $formEmail = new \Project\Form\DocumentEmailForm($this->getEntityManager());
        
        $this->getView()
                ->setVariable('formEmail', $formEmail)
                ->setVariable('documents', $documents)
                ->setTemplate('project/projectitemdocument/index.phtml');
        
		return $this->getView();
    }

}

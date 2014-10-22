<?php
namespace Client\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Json\Json;

use Application\Controller\AuthController;

use Zend\Mvc\MvcEvent;

use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

use Zend\View\Model\JsonModel;
use Zend\Paginator\Paginator;

use Client\Form\SetupForm;
use Project\Form\ProjectCreateForm;


class ClientitemController extends ClientSpecificController
{

    public function onDispatch(MvcEvent $e) {
        return parent::onDispatch($e);
    }
    
    public function indexAction()
    {
        $this->setCaption('Client: '.$this->getClient()->getName());
        $em = $this->getEntityManager();
        
        $contacts = $em->getRepository('Contact\Entity\Contact')->findByClientId($this->getClient()->getclientId());
        $buildings = $em->getRepository('Client\Entity\Building')->findByClientId($this->getClient()->getclientId());
        
        $audit = $em->getRepository('Application\Entity\Audit')->findByClientId($this->getClient()->getClientId(), true, array(
            'max' => 8,
            'auto'=> true,
        ));
        
        $activities = $em->getRepository('Application\Entity\Activity')->findByClientId($this->getClient()->getClientId(), true, array(
            'max' => 8,
            'auto'=> true,
        ));
        
        $formActivity = new \Application\Form\ActivityAddForm($em, array(
            'clientId'=>$this->getClient()->getClientId(),
        ));
        
        $formActivity
                ->setAttribute('action', '/dashboard/activity/')
                ->setAttribute('class', 'form-nomargin');
        
        $this->getView()
                ->setVariable('formActivity', $formActivity)
                ->setVariable('user', $this->getUser())
                ->setVariable('activities', $activities)
                ->setVariable('contacts', $contacts)
                ->setVariable('buildings', $buildings)
                ->setVariable('audit', $audit);
        
        return $this->getView();
    }
    
    public function setupAction() {
        $saveRequest = ($this->getRequest()->isXmlHttpRequest());
        
        $this->setCaption('Client Configuration');
        $form = new SetupForm($this->getEntityManager());
        $form->setAttribute('action', $this->getRequest()->getUri()); // set URI to current page
        
        $form->bind($this->getClient());
        $form->setBindOnValidate(true);
        
        if ($saveRequest) {
            try {
                if (!$this->getRequest()->isPost()) {
                    throw new \Exception('illegal method');
                }
                
                $post = $this->getRequest()->getPost();

                $form->setData($post);
                if ($form->isValid()) {
                    
                    $form->bindValues();
                    $this->getEntityManager()->flush();
                    $data = array('err'=>false);
                    
                    $this->AuditPlugin()->auditClient(102, $this->getUser()->getUserId(), $this->getClient()->getClientId());
                } else {
                    $data = array('err'=>true, 'info'=>$form->getMessages());
                }
            } catch (\Exception $ex) {
                $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
            }

            return new JsonModel(empty($data)?array('err'=>true):$data);/**/
        } else {
            $this->getView()->setVariable('form', $form);
            return $this->getView();
        }
    }
    
    
    /**
     * Add new project action metho
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function newProjectAction() {
        $saveRequest = ($this->getRequest()->isXmlHttpRequest());
        
        $form = new ProjectCreateForm($this->getEntityManager());
        $form->setAttribute('action', $this->getRequest()->getUri()); // set URI to current page
        $form->setAttribute('class', 'form-horizontal');
        
        // set default values
        $formAddr = new \Contact\Form\AddressForm($this->getEntityManager());
        $formAddr->setAttribute('action', '/client-'.$this->getClient()->getClientId().'/address-add/'); // set URI to current page
        $formAddr->setAttribute('class', 'form-horizontal');
        
        
        if ($saveRequest) {
            try {
                if (!$this->getRequest()->isPost()) {
                    throw new \Exception('illegal method');
                }
                
                
                $post = $this->getRequest()->getPost();
                $post['weighting'] = 0;
                //print_r($post); 

                $project = new \Project\Entity\Project();
                $form->bind($project);
                $form->setBindOnValidate(true);

                $form->setData($post);
                if ($form->isValid()) {
                    $notes = empty($post['note'])?array():array_filter($post['note']);
                    $notes = json_encode($notes);
                    $project->setNotes($notes);
                    
                    $form->bindValues();
                    $project->setClient($this->getClient());
                    $project->setStatus($this->getEntityManager()->find('Project\Entity\Status', 1));
                    //$project->setStatus($this->getEntityManager()->find('Project\Entity\Status', $form->get('status')->getValue()));
                    $space = new \Space\Entity\Space();
                    $space->setRoot(true);
                    $space->setName('root');
                    $space->setProject($project);
                    
                    $this->getEntityManager()->persist($project);
                    $this->getEntityManager()->persist($space);
                    $this->getEntityManager()->flush();
                    

                    
                    $this->flashMessenger()->addMessage(array(
                        'The project '.str_pad($this->getClient()->getClientId(), 5, "0", STR_PAD_LEFT).'-'.str_pad($project->getProjectId(), 5, "0", STR_PAD_LEFT).' has been added successfully', 'Success!'
                    ));
                    
                    $this->AuditPlugin()->auditProject(200, $this->getUser()->getUserId(), $this->getClient()->getClientId(), $project->getProjectId());
                    
                    $data = array('err'=>false, 'cid'=>$this->getClient()->getClientId(), 'pid'=>$project->getProjectId());
                } else {
                    $data = array('err'=>true, 'info'=>$form->getMessages());
                }
            } catch (\Exception $ex) {
                $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
            }

            return new JsonModel(empty($data)?array('err'=>true):$data);/**/
        } else {
            $this->setCaption('Create Project');

            $this->getView()->setVariable('form', $form);
            $this->getView()->setVariable('formAddr', $formAddr);
            return $this->getView();
        }
    }
    
    
    
    /**
     * project listing method
     * @return \Zend\View\Model\JsonModel 
     * @throws \Exception
     */
    public function projectsAction() {
        if (!$this->request->isXmlHttpRequest()) {
            throw new \Exception('illegal request type');
        }
        

        return $this->getProjectsData();
    }
    
    public function jobsAction() {

        if (!$this->request->isXmlHttpRequest()) {
            throw new \Exception('illegal request type');
        }
        
        return $this->getProjectsData(true);
        
    }
    
    
    /**
     * add a new address to client
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function addressAddAction() {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception('illegal request format');
            }
            
            if (!$this->getRequest()->isPost()) {
                throw new \Exception('illegal method');
            }
            
            // create form
            $form = new \Contact\Form\AddressForm($this->getEntityManager());
            $address = new \Contact\Entity\Address();

            // bind object to form
            $form->bind($address);

            $post = $this->getRequest()->getPost();
            $form->setData($post);
            if ($form->isValid()) {
                $address->setClient($this->getClient());
                $this->getEntityManager()->persist($address);
                $this->getEntityManager()->flush();

                $data = array('err'=>false, 'aid'=>$address->getAddressId());
            } else {
                $data = array('err'=>true, 'info'=>$form->getMessages());
            }

        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }

        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    
    /**
     * add a new address to client
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function addressFindAction() {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception('illegal request format');
            }
            
            // use repository to find addresses
            $data = array('err'=>false, 'addr'=>$this->getEntityManager()->getRepository('Contact\Entity\Address')->findByClientId($this->getClient()->getclientId(), true));
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }

        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    
    /**
     * return paginated projects data
     * @param boolean $job
     * @return \Zend\View\Model\JsonModel
     */
    protected function getProjectsData($job=false) {
        $em = $this->getEntityManager();
        $length = $this->params()->fromQuery('iDisplayLength', 10);
        $start = $this->params()->fromQuery('iDisplayStart', 1);
        $keyword = $this->params()->fromQuery('sSearch','');
        $params = array(
            'keyword'=>trim($keyword),
            'orderBy'=>array()
        );
        
        if ($job) {
            $params['job'] = true;
        } else {
            $params['project'] = true;
        }
        
        $orderBy = array(
            0=>'id',
            1=>'name',
            3=>'status'
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

        
        $paginator = $em->getRepository('Project\Entity\Project')->findPaginateByClientId($this->getClient()->getclientId(), $length, $start, $params);

        $data = array(
            "sEcho" => intval($this->params()->fromQuery('sEcho', false)),
            "iTotalDisplayRecords" => $paginator->getTotalItemCount(),
            "iTotalRecords" => $paginator->getcurrentItemCount(),
            "aaData" => array()
        );/**/

        
        foreach ($paginator as $page) {
            //$url = $this->url()->fromRoute('client',array('id'=>$page->getclientId()));
            $data['aaData'][] = array (
                '<a href="javascript:" class="action-project-edit"  pid="'.$page->getProjectId().'">'.str_pad($page->getClient()->getClientId(), 5, "0", STR_PAD_LEFT).'-'.str_pad($page->getProjectId(), 5, "0", STR_PAD_LEFT).'</a>',
                $page->getName(),
                0,
                '<span style="width: 95%" class="label label-'.(($page->getStatus()->getweighting()==0)?(($page->getStatus()->gethalt()==1)?'important':'info'):'success').' label-mini">'.ucwords($page->getStatus()->getName()).'</span>',
                '<button class="btn btn-success action-client-edit" pid="'.$page->getProjectId().'" ><i class="icon-copy"></i></button>&nbsp;'
                . '<button class="btn btn-primary action-client-edit" pid="'.$page->getProjectId().'" ><i class="icon-pencil"></i></button>&nbsp;'
                . ($job?'':'<button pid="'.$page->getProjectId().'" class="btn btn-danger action-project-delete"><i class="icon-trash "></i></button>'),
            );
        }        
        
        
        return new JsonModel($data);/**/
    }
    
   
    function collaboratorsAction() {
        $saveRequest = ($this->getRequest()->isXmlHttpRequest());
        
        $form = new \Project\Form\CollaboratorsForm($this->getEntityManager());
        $form
            ->setAttribute('class', 'form-horizontal')
            ->setAttribute('action', '/client-'.$this->getClient()->getClientId().'/collaborators/');

        $form->bind($this->getClient());
        
        if ($saveRequest) {
            try {
                if (!$this->getRequest()->isPost()) {
                    throw new \Exception('illegal method');
                }
                
                $post = $this->params()->fromPost();
                
                if (empty($post['collaborators'])) {
                    $post['collaborators'] = array();
                }
                
                $hydrator = new DoctrineHydrator($this->getEntityManager(),'Client\Entity\Client');
                $hydrator->hydrate($post, $this->getClient());

                $this->getEntityManager()->persist($this->getClient());
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
    
    /**
     * Add note to client
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
            
            $notes = $this->getClient()->getNotes();
            $notes = json_decode($notes, true);
            if (empty($notes)) {
                $notes = array();
            }
            
            $noteIdx = time();
            $notes[$noteIdx] = $note;
            $noteCnt = count($notes);
            $notes = json_encode($notes);
            
            $this->getClient()->setNotes($notes);
            $this->getEntityManager()->persist($this->getClient());
            $this->getEntityManager()->flush();
            
            if ($noteCnt==1) {
                $this->flashMessenger()->addMessage(array('The client note has been added successfully', 'Success!'));
            } 

            $data = array('err'=>false, 'cnt'=>$noteCnt, 'id'=>$noteIdx);
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }

}
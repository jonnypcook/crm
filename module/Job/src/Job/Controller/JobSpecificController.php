<?php
namespace Job\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Application\Controller\AuthController;

use Project\Service\DocumentService;

use Zend\Mvc\MvcEvent;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

class JobSpecificController extends AuthController
{
    /**
     *
     * @var Project\Entity\Project
     */
    private $project;
    
    public function onDispatch(MvcEvent $e) {
        $cid = (int) $this->params()->fromRoute('cid', 0);
        $jid = (int) $this->params()->fromRoute('jid', 0);
        
        if (empty($cid)) {
            return $this->redirect()->toRoute('clients');
        } 
        
        if (empty($jid)) {
            return $this->redirect()->toRoute('clients');
        } 
        
        if (!($project=$this->getEntityManager()->getRepository('Project\Entity\Project')->findByProjectId($jid, array('client_id'=>$cid)))) {
            return $this->redirect()->toRoute('client', array('id'=>$cid));
        }
        
        if (($project->getType()->getTypeId()==3)) { // this is not a trial
            return $this->redirect()->toRoute('trial', array('cid'=>$cid, 'tid'=>$jid));
        }
        
        if (($project->getStatus()->getJob()==0) && ($project->getStatus()->getWeighting()<1)) {
            return $this->redirect()->toRoute('project', array('cid'=>$cid, 'pid'=>$jid));
        }
        
        // check privileges
        if ($project->getClient()->getUser()->getUserId()!=$this->identity()->getUserId()) {
            if (!$this->isGranted('admin.all')) {
                if (!$this->isGranted('project.share') || ($this->isGranted('project.share') && ($project->getClient()->getUser()->getCompany()->getCompanyId() != $this->identity()->getCompany()->getCompanyId()))) {
                    $passed = false;
                    foreach ($project->getCollaborators() as $user) {
                        if ($user->getUserId()==$this->identity()->getUserId()) {
                            $passed = true;
                            break;
                        }
                    }
                    if (!$passed) {
                        foreach ($project->getClient()->getCollaborators() as $user) {
                            if ($user->getUserId()==$this->identity()->getUserId()) {
                                $passed = true;
                                break;
                            }
                        }
                        if (!$passed) {
                            return $this->redirect()->toRoute('clients');
                        }
                    }
                    
                } 
            }
        }
        
        $this->setProject($project);
        $this->amendNavigation();
        
        return parent::onDispatch($e);
    }
    
    
    /**
     * get project
     * @return \Project\Entity\Project
     */
    public function getProject() {
        return $this->project;
    }

    /**
     * set project
     * @param \Project\Entity\Project $project
     * @return \Project\Controller\ProjectitemController
     */
    public function setProject(\Project\Entity\Project $project) {
        $this->project = $project;
        $this->getView()->setVariable('project', $project);
        return $this;
    }
    
    
    /**
     * get project
     * @return \Client\Entity\Client
     */
    public function getClient() {
        return $this->project->getClient();
    }

    protected $model_service;
    
    protected function getModelService()
    {
        if (!$this->model_service) {
            $this->model_service = $this->getServiceLocator()->get('Model');
        }

        return $this->model_service;
    }


    
    public function amendNavigation() {
        // check current location
        $action = $this->params('action');
        $documentMode = ($this->params('controller')=='Project\Controller\ProjectItemDocumentController');
        $standardMode = !$documentMode;
        
        // get client
        $project = $this->getProject();
        $client = $project->getClient();
        
        // grab navigation object
        $navigation = $this->getServiceLocator()->get('navigation');
        
        $navigation->addPage(array(
            'type' => 'uri',
            'ico'=> 'icon-user',
            'order'=>0,
            'uri'=> '/client-'.$client->getClientId().'/',
            'label' => 'Client #'.str_pad($client->getClientId(), 5, "0", STR_PAD_LEFT),
        ));/**/

        $navigation->addPage(array(
            'type' => 'uri',
            'active'=>true,  
            'ico'=> 'icon-user',
            'order'=>1,
            'uri'=> '/client/',
            'label' => 'Clients',
            'skip' => true,
            'pages' => array(
                array (
                    'type' => 'uri',
                    'active'=>true,  
                    'ico'=> 'icon-user',
                    'skip' => true,
                    'uri'=> '/client-'.$client->getClientId().'/',
                    'label' => $client->getName(),
                    'mlabel' => 'Client #'.str_pad($client->getClientId(), 5, "0", STR_PAD_LEFT),
                    'pages' => array(
                        array(
                            'type' => 'uri',
                            'active'=>true,  
                            'ico'=> 'icon-tag',
                            'uri'=> '/client-'.$client->getClientId().'/job-'.$project->getProjectId().'/',
                            'label' => $project->getName(),
                            'mlabel' => 'Job: '.str_pad($client->getClientId(), 5, "0", STR_PAD_LEFT).'-'.str_pad($project->getProjectId(), 5, "0", STR_PAD_LEFT),
                            'pages' => array(
                                array(
                                    'label' => 'Dashboard',
                                    'active'=>($standardMode && ($action=='index')),  
                                    'uri'=> '/client-'.$client->getClientId().'/job-'.$project->getProjectId().'/',
                                    'title' => ucwords($project->getName()).' Overview',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='setup')),  
                                    'label' => 'Configuration',
                                    'uri'=> '/client-'.$client->getClientId().'/job-'.$project->getProjectId().'/setup/',
                                    'title' => ucwords($project->getName()).' Setup',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='system')),  
                                    'label' => 'System Setup',
                                    'uri'=> '/client-'.$client->getClientId().'/job-'.$project->getProjectId().'/system/',
                                    'title' => ucwords($project->getName()).' System Configuration',
                                ),
                                array(
                                    'active'=>($standardMode && (($action=='model') || ($action=='forecast') || ($action=='breakdown'))),  
                                    'label' => 'System Model',
                                    'uri'=> '/client-'.$client->getClientId().'/job-'.$project->getProjectId().'/model/',
                                    'title' => ucwords($project->getName()).' System Model',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='collaborators')),  
                                    'permissions'=>array('project.collaborate'),
                                    'label' => 'Collaborators',
                                    'uri'=> '/client-'.$client->getClientId().'/job-'.$project->getProjectId().'/collaborators/',
                                    'title' => ucwords($project->getName()).' Collaborators',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='serials')),  
                                    'label' => 'Serials',
                                    'uri'=> '/client-'.$client->getClientId().'/job-'.$project->getProjectId().'/serials/',
                                    'title' => ucwords($project->getName()).' Serials',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='telemetry')),  
                                    'label' => 'Telemetry',
                                    'uri'=> '/client-'.$client->getClientId().'/job-'.$project->getProjectId().'/telemetry/',
                                    'title' => ucwords($project->getName()).' Telemetry',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='deliverynote')),  
                                    'label' => 'Delivery Notes',
                                    'uri'=> '/client-'.$client->getClientId().'/job-'.$project->getProjectId().'/deliverynote/',
                                    'title' => ucwords($project->getName()).' Delivery Notes',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='document')),  
                                    'permissions'=>array('project.write'),
                                    'label' => 'Document Wizard',
                                    'uri'=> '/client-'.$client->getClientId().'/job-'.$project->getProjectId().'/document/',
                                    'title' => ucwords($project->getName()).' Document Wizard',
                                ),
                            )
                        )
                    )
                )
            )
        ));
        
        

        
    }
    
}
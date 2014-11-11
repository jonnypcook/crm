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
        
        if (($project->getStatus()->getJob()==0) && ($project->getStatus()->getWeighting()<1)) {
            return $this->redirect()->toRoute('project', array('cid'=>$cid, 'pid'=>$jid));
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
                            )
                        )
                    )
                )
            )
        ));
        
        $navigation->addPage(array(
            'type' => 'uri',
            'ico'=> 'icon-user',
            'order'=>0,
            'uri'=> '/client-'.$client->getClientId().'/',
            'label' => 'Client #'.str_pad($client->getClientId(), 5, "0", STR_PAD_LEFT),
        ));/**/

        
    }
    
}
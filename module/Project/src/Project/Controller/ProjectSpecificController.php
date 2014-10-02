<?php
namespace Project\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Application\Controller\AuthController;

use Zend\Mvc\MvcEvent;


class ProjectSpecificController extends AuthController
{
    /**
     *
     * @var Project\Entity\Project
     */
    private $project;
    
    public function onDispatch(MvcEvent $e) {
        $cid = (int) $this->params()->fromRoute('cid', 0);
        $pid = (int) $this->params()->fromRoute('pid', 0);
        
        if (empty($cid)) {
            return $this->redirect()->toRoute('clients');
        } 
        
        if (empty($pid)) {
            return $this->redirect()->toRoute('clients');
        } 
        
        if (!($project=$this->getEntityManager()->getRepository('Project\Entity\Project')->findByProjectId($pid, array('client_id'=>$cid)))) {
            return $this->redirect()->toRoute('client', array('id'=>$cid));
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
        $documentMode = ($this->params('controller')=='Project\Controller\ProjectItemDocument');
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
                            'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/',
                            'label' => $project->getName(),
                            'mlabel' => 'Project: '.str_pad($client->getClientId(), 5, "0", STR_PAD_LEFT).'-'.str_pad($project->getProjectId(), 5, "0", STR_PAD_LEFT),
                            'pages' => array(
                                array(
                                    'label' => 'Dashboard',
                                    'active'=>($standardMode && ($action=='index')),  
                                    'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/',
                                    'title' => ucwords($project->getName()).' Overview',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='setup')),  
                                    'label' => 'Configuration',
                                    'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/setup/',
                                    'title' => ucwords($project->getName()).' Setup',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='system')),  
                                    'permissions'=>array('project.write'),
                                    'label' => 'System Setup',
                                    'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/system/',
                                    'title' => ucwords($project->getName()).' System Setup',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='telemetry')),  
                                    'label' => 'Telemetry',
                                    'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/telemetry/',
                                    'title' => ucwords($project->getName()).' System Setup',
                                ),
                                array(
                                    'active'=>($standardMode && (($action=='model') || ($action=='forecast') || ($action=='breakdown'))),  
                                    'label' => 'System Model',
                                    'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/model/',
                                    'title' => ucwords($project->getName()).' System Model',
                                ),
                                array(
                                    'active'=>($documentMode && ($action=='index')),  
                                    'permissions'=>array('project.write'),
                                    'label' => 'Document Wizard',
                                    'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/document/index/',
                                    'title' => ucwords($project->getName()).' Document Wizard',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='filemanager')),  
                                    'label' => 'File Manager',
                                    'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/filemanager/',
                                    'title' => ucwords($project->getName()).' File Manager',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='collaborators')),  
                                    'permissions'=>array('project.collaborate'),
                                    'label' => 'Collaborators',
                                    'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/collaborators/',
                                    'title' => ucwords($project->getName()).' Collaborators',
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
<?php
namespace Project\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Application\Controller\AuthController;

use Project\Service\DocumentService;

use Zend\Mvc\MvcEvent;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

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
        
        if (($project->getStatus()->getJob()==1) || (($project->getStatus()->getWeighting()>=1) &&  ($project->getStatus()->getHalt()==1))) {
            return $this->redirect()->toRoute('job', array('cid'=>$cid, 'jid'=>$pid));
        }
        
        $this->setProject($project);
        $this->amendNavigation();
        
        if (!empty($this->documentService)) {
            $this->documentService->setProject($project);
        }
        
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
        $documentMode = ($this->params('controller')=='Project\Controller\ProjectItemDocumentController');
        $exportMode = ($this->params('controller')=='Project\Controller\ProjectItemExport');
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
                                    'active'=>($standardMode && ($action=='bluesheet')),  
                                    'label' => 'Blue Sheet',
                                    'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/bluesheet/',
                                    'title' => ucwords($project->getName()).' Blue Sheet',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='system')),  
                                    'permissions'=>array('project.write'),
                                    'label' => 'System Setup',
                                    'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/system/',
                                    'title' => ucwords($project->getName()).' System Setup',
                                    'pages' => array(
                                        array(
                                            'label' => 'Export Project',
                                            'active'=>($exportMode && ($action=='index')),  
                                            'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/export/',
                                            'title' => 'Export Project',
                                        ),
                                        array(
                                            'label' => 'Create Trial',
                                            'active'=>($exportMode && ($action=='trial')),  
                                            'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/export/trial/',
                                            'title' => 'Create Trial',
                                        )
                                    )
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
                                    'active'=>($documentMode && ($action=='viewer')),  
                                    'label' => 'Document Manager',
                                    'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/document/viewer/',
                                    'title' => ucwords($project->getName()).' Document Manager',
                                ),
                                array(
                                    'active'=>($standardMode && ($action=='email')),  
                                    'label' => 'Email Threads',
                                    'uri'=> '/client-'.$client->getClientId().'/project-'.$project->getProjectId().'/email/',
                                    'title' => ucwords($project->getName()).' Email Threads',
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
    
    /**
     * document service
     * @var \Project\Service\DocumentService 
     */
    protected $documentService;
    
    /**
     * get document service
     * @return \Project\Service\DocumentService
     */
    public function getDocumentService() {
        return $this->documentService;
    }

    /**
     * set document service
     * @param \Project\Service\DocumentService $documentService
     * @return \Project\Controller\ProjectSpecificController
     */
    public function setDocumentService(\Project\Service\DocumentService $documentService) {
        $this->documentService = $documentService;
        return $this;
    }


    /**
     * save config
     * @param type $name
     * @return \Project\Entity\Save
     * @throws \Project\Controller\Exception
     */
    protected function saveConfig($name=null) {
        try {
            // hydrate the doctrine entity
            $em = $this->getEntityManager();
            $save = new \Project\Entity\Save();
            $hydrator = new DoctrineHydrator($em,'Project\Entity\Save');
            $hydrator->hydrate(
                array (
                    'name' => $name,
                    'project' => $this->getProject()->getProjectId(),
                    'user' => $this->getUser()->getUserId(),
                ),
                $save);

            // create the serializer that we will use to store "flattened" data
            $serializer =  \Zend\Serializer\Serializer::factory('phpserialize');
            
            // get system data
            $query = $em->createQuery('SELECT s.label, s.cpu, s.ppu, s.ippu, s.quantity, '
                    . 's.hours, s.legacyWatts, s.legacyQuantity, s.legacyMcpu, '
                    . 's.lux, s.occupancy, s.locked, '
                    . 'sp.spaceId,'
                    . 'l.legacyId,'
                    . 'p.productId '
                    . 'FROM Space\Entity\System s '
                    . 'JOIN s.space sp '
                    . 'JOIN s.product p '
                    . 'LEFT JOIN s.legacy l '
                    . 'WHERE sp.project='.$this->getProject()->getProjectId());
            $systems = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
            
            $system = array();
            foreach ($systems as $item) {
                $system[] = array (
                    $item['cpu'],
                    $item['ppu'],
                    $item['ippu'],
                    $item['quantity'],
                    $item['hours'],
                    $item['legacyWatts'],
                    $item['legacyQuantity'],
                    $item['legacyMcpu'],
                    $item['lux'],
                    $item['occupancy'],
                    $item['label'],
                    $item['locked'],
                    $item['productId'],
                    $item['spaceId'],
                    $item['legacyId'],
                );
            }
            
            
            $data = array(
                'setup'=>array(
                    'co2'=>$this->getProject()->getCo2(),
                    'fuelTariff'=>$this->getProject()->getFuelTariff(),
                    'rpi'=>$this->getProject()->getRpi(),
                    'epi'=>$this->getProject()->getEpi(),
                    'mcd'=>$this->getProject()->getMcd(),
                    'factorPrelim'=>$this->getProject()->getFactorPrelim(),
                    'factorOverhead'=>$this->getProject()->getFactorOverhead(),
                    'factorManagement'=>$this->getProject()->getFactorManagement(),
                    'eca'=>$this->getProject()->getEca(),
                    'maintenance'=>$this->getProject()->getMaintenance(),
                    'carbon'=>$this->getProject()->getCarbon(),
                    'model'=>$this->getProject()->getModel(),
                    'weighting'=>$this->getProject()->getWeighting(),
                    'ibp'=>$this->getProject()->getIbp(),
                    'financeYears'=>!empty($this->getProject()->getFinanceYears())?$this->getProject()->getFinanceYears()->getFinanceYearsId():null,
                    'financeProvider'=>!empty($this->getProject()->getFinanceProvider())?$this->getProject()->getFinanceProvider()->getFinanceProviderId():null,
                ),
                'system'=>$system,
            );
            
            //foreach ($system as $)
            $config = $serializer->serialize($data); //<~ serialized !
            $save->setConfig($config);

            // now compare checksums with last saved item
            $qb = $em->createQueryBuilder();
            $qb
                ->select('s.checksum, s.saveId')
                ->from('Project\Entity\Save', 's')
                ->where('s.project = '.$this->getProject()->getProjectId())
                ->orderBy('s.activated', 'DESC');

            $query  = $qb->getQuery();
            $query->setMaxResults(1);
            try {
                $item = $query->getSingleResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
                if (!empty($item)) {
                    if ($item['checksum']==$save->getChecksum()) {
                        $save->setSaveId($item['saveId']);
                        return $save;
                    }
                } 

            } catch (\Exception $ex2) {
                // ignore
            }
            
            
            // persist object
            $em->persist($save);
            $em->flush();

            
            
            return $save;
        } catch (Exception $e) {
            throw $e;
        }
    }

    
}
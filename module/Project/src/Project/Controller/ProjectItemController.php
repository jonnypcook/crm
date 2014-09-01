<?php
namespace Project\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Json\Json;

use Application\Entity\User; 
use Application\Controller\AuthController;

use Project\Form\SetupForm;
use Space\Form\SpaceCreateForm;

use Zend\Mvc\MvcEvent;

use Zend\View\Model\JsonModel;

use Zend\Http\Request;
use Zend\Http\Client;
use Zend\Stdlib\Parameters;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

class ProjectitemController extends ProjectSpecificController
{
    public function indexAction()
    {
        $this->setCaption('Project Dashboard');
        
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT p.model, p.eca, pt.name AS productType, SUM(s.quantity) AS quantity, SUM(s.ppu*s.quantity) AS price FROM Space\Entity\System s JOIN s.space sp JOIN s.product p JOIN p.type pt WHERE sp.project='.$this->getProject()->getProjectId().' GROUP BY s.product');
        $system = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $audit = $em->getRepository('Application\Entity\Audit')->findByProjectId($this->getProject()->getProjectId(), true, array(
            'max' => 8,
            'auto'=> true,
        ));
        
        $activities = $em->getRepository('Application\Entity\Activity')->findByProjectId($this->getProject()->getProjectId(), true, array(
            'max' => 8,
            'auto'=> true,
        ));

        $formActivity = new \Application\Form\ActivityAddForm($em, array(
            'projectId'=>$this->getProject()->getProjectId(),
        ));
        
        $formActivity
                ->setAttribute('action', '/dashboard/activity/')
                ->setAttribute('class', 'form-nomargin');
        
        $this->getView()
                ->setVariable('formActivity', $formActivity)
                ->setVariable('user', $this->getUser())
                ->setVariable('audit', $audit)
                ->setVariable('activities', $activities)
                ->setVariable('system', $system);
        
		return $this->getView();
    }
    
    
    public function modelAction()
    {
        //$tm = 253402300798999;
        //echo date('Y-m-d H:i:s', $tm);
        //  die();      
        /*$client = new Zend_Rest_Client('http://framework.zend.com/rest');
        
        $request = new Request();
        $request->getHeaders()->addHeaders(array(
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'
        ));
        $request->setUri('https://testing.wattzo.com/api/accounts/login/');
        $request->setMethod('GET');
        $request->setPost(new Parameters(array('email' => 'richard.whitbread@8point3led.co.uk', 'password'=>'Meganmary1')));

        $client = new \Zend\Http\Client(null, array(
                      'adapter' => 'Zend\Http\Client\Adapter\Socket',
                      'sslverifypeer' => false
                  ));
        $response = $client->dispatch($request);
        $data = $response->getBody();//json_decode($response->getBody(), true);
        
        print_r($data);
        echo 'moo';
        die();/**/
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

    /**
     * system management action
     * @return Zend\View\Model\ViewModel
     */
    public function systemAction()
    {
        $this->setCaption('System Setup');
        
        $spaces = $this->getEntityManager()->getRepository('Space\Entity\Space')->findByProjectId($this->getProject()->getProjectId(), array('root'=>true));
        // if we don't have a root space (non physical default space) then we need to create 
        if (empty($spaces)) {
            $space = new \Space\Entity\Space();
            $space->setRoot(true);
            $space->setName('root');
            $space->setProject($this->getProject());
            $this->getEntityManager()->persist($space);
            $this->getEntityManager()->flush();
        }
        
        
        foreach ($spaces as $spaceItem) {
            $space = $spaceItem;
            break;
        }
        
        // space create form
        $form = new SpaceCreateForm($this->getEntityManager(), $this->getProject()->getClient()->getClientId());
        $form->setAttribute('action', '/client-'.$this->getProject()->getClient()->getClientId().'/project-'.$this->getProject()->getProjectId().'/newspace/'); // set URI to current page
        $form->setAttribute('class', 'form-horizontal');
        
        // system create form
        $formSystem = new \Space\Form\SpaceAddProductForm($this->getEntityManager());
        $formSystem->setAttribute('class', 'form-horizontal');
        $formSystem->setAttribute('action', '/client-'.$this->getProject()->getClient()->getClientId().'/project-'.$this->getProject()->getProjectId().'/space-'.$space->getSpaceId().'/addsystem/');
        $system = new \Space\Entity\System();
        $formSystem->bind($system);
        
        $buildings = $this->getEntityManager()->getRepository('Client\Entity\Building')->findByProjectId($this->getProject()->getProjectId(), array('order'=>'building'));
        
        // get product information
        $query = $this->getEntityManager()->createQuery("SELECT p.model, p.ppu, p.eca, p.pwr, p.productId, b.name as brand, t.name as type, t.service "
                . "FROM Product\Entity\Product p "
                . "JOIN p.brand b "
                . "JOIN p.type t "
                . "WHERE p.active = 1 "
                . "ORDER BY b.name ASC, p.model ASC");
        $products = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $query = $this->getEntityManager()->createQuery("SELECT l.legacyId, l.description, l.quantity, l.pwr_item, l.pwr_ballast, l.emergency, l.dim_item, l.dim_unit, c.maintenance, c.name as category FROM Product\Entity\Legacy l JOIN l.category c ORDER BY l.category ASC, l.description ASC");
        $legacies = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        $systems=$this->getEntityManager()->getRepository('Space\Entity\System')->findBySpaceId($space->getSpaceId(), array('array'=>true));
        
        // get backup information
        $query = $this->getEntityManager()->createQuery("SELECT ps.saveId, ps.name, ps.created "
        . "FROM Project\Entity\Save ps "
        . "WHERE ps.project = {$this->getProject()->getProjectId()} "
        . "ORDER BY ps.created DESC");
        $saves = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        
        $this->getView()
            ->setVariable('saves', $saves)
            ->setVariable('space', $space)
            ->setVariable('form', $form)
            ->setVariable('formSystem', $formSystem)
            ->setVariable('products', $products)
            ->setVariable('legacies', $legacies)
            ->setVariable('buildings', $buildings)
            ->setVariable('systems', $systems);
        
		return $this->getView();
    }

    /**
     * list spaces in building
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function spaceListAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }

            $post = $this->getRequest()->getPost();
            if (empty($post['bid'])) {
                throw new \Exception('building identifier not found');
            }
            
            if (!preg_match('/^[\d]+$/',$post['bid'])) {
                throw new \Exception('building identifier invalid');
            }
            
            $spaces = $this->getEntityManager()->getRepository('Space\Entity\Space')->findByBuildingId($post['bid'], $this->getProject()->getProjectId(), true);
            
            $data = array('err'=>false, 'spaces'=>$spaces);
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }

    public function setupAction()
    {
        $saveRequest = ($this->getRequest()->isXmlHttpRequest());
        

        $this->setCaption('Project Configuration');
        $form = new SetupForm($this->getEntityManager());
        $form->setAttribute('action', $this->getRequest()->getUri()); // set URI to current page
        
        $form->bind($this->getProject());
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
                    $this->AuditPlugin()->auditProject(202, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId());
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
     * Add new space action metho
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function newSpaceAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }

            $post = $this->getRequest()->getPost();
            $form = new SpaceCreateForm($this->getEntityManager(), $this->getProject()->getClient()->getClientId());
            $space = new \Space\Entity\Space();
            $form->bind($space);
            //$form->setBindOnValidate(true);

            $form->setData($post);
            if ($form->isValid()) {
                $space->setProject($this->getProject());
                $form->bindValues();
                $this->getEntityManager()->persist($space);
                $this->getEntityManager()->flush();
                    
                $this->flashMessenger()->addMessage(array(
                    'The space &quot;'.$space->getName().'&quot; has been added successfully', 'Success!'
                ));
                    
                $data = array('err'=>false, 'url'=>'/client-'.$this->getProject()->getClient()->getClientId().'/project-'.$this->getProject()->getProjectId().'/space-'.$space->getSpaceId().'/');
                $this->AuditPlugin()->auditSpace(301, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId(), $space->getSpaceId());
            } else {
                $data = array('err'=>true, 'info'=>$form->getMessages());
            }
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    
    public function telemetryAction()
    {
        $this->setCaption('Project Telemetry and Control Management');
        
        $em = $this->getEntityManager();
        
		return $this->getView();
    }
    
    
    public function configRefreshAction() {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception('illegal request format');
            }
            
            
            $query = $this->getEntityManager()->createQuery("SELECT ps.saveId, ps.name, ps.created "
                . "FROM Project\Entity\Save ps "
                . "WHERE ps.project = {$this->getProject()->getProjectId()} "
                . "ORDER BY ps.created DESC");
                
            $res = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
            $saves = array();
            foreach ($res as $save) {
                $saves[] = array (
                    $save['saveId'],
                    $save['created']->format('jS F Y H:i:s').(empty($save['name'])?'':' - '.$save['name']),
                    
                );
            } 
            
            // use repository to find addresses
            $data = array('err'=>false, 'saves'=>$saves);
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }

        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    
    /**
     * action to load config
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function configLoadAction() {
        //$obj = $serializer->unserialize($y);
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            $saveId = $this->params()->fromPost('saveId', false);
            
            if (!preg_match('/^[\d]+$/', $saveId)) {
                throw new \Exception('illegal request');
            }
            $em = $this->getEntityManager();
            $save = $em->find('Project\Entity\Save', $saveId);
            
            if (!($save instanceof \Project\Entity\Save)) {
                throw new \Exception('invalid save request');
            }
            
            // autosave if enabled
            $saveMe = $this->params()->fromPost('autoSave', false);
            if (!empty($saveMe)) {
                $this->saveConfig();
            }
            
            // step 1: deserialize
            $serializer =  \Zend\Serializer\Serializer::factory('phpserialize');
            $config = $serializer->unserialize($save->getConfig());
            
            
            // step 2: update project
            $hydrator = new DoctrineHydrator($em,'Project\Entity\Project');
            $hydrator->hydrate(
                $config['setup'],
                $this->getProject());

            $em->persist($this->getProject());/**/
            
            // step 3: delete current configuration
            $osystems = $em->getRepository('Space\Entity\System')->findByProjectId($this->getProject()->getProjectId());
            if (!empty($osystems)) {
                foreach ($osystems as $osystem) {
                    $em->remove($osystem);
                }
            }
            
            $spaces = array();
            // step 3: parse saved data
            $hydrator = new DoctrineHydrator($em,'Space\Entity\System');
            foreach ($config['system'] as $system) {
                $systemObj = new \Space\Entity\System();
                $hydrator->hydrate(
                    array (
                    'cpu' => $system[0],
                    'ppu' => $system[1],
                    'ippu' => $system[2],
                    'quantity' => $system[3],
                    'hours' => $system[4],
                    'legacyWatts' => $system[5],
                    'legacyQuantity' => $system[6],
                    'legacyMcpu' => $system[7],
                    'lux' => $system[8],
                    'occupancy' => $system[9],
                    'label' => $system[10],
                    'locked' => $system[11],
                    'product' => $system[12],
                    'space' => $system[13],
                    'legacy' => $system[14],
                    ),
                    $systemObj
                );
                $em->persist($systemObj);
                $spaces[$system[13]] = true;
            }/**/
            
            // step 4: ensure spaces are switched on
            $sspaces = $em->getRepository('Space\Entity\Space')->findByProjectId($this->getProject()->getProjectId());
            if (!empty($sspaces)) {
                foreach ($sspaces as $sspace) {
                    if (isset($spaces[$sspace->getSpaceId()])){
                        if ($sspace->getDeleted()) {
                            $sspace->setDeleted(false);
                            if ($sspace->getBuilding()->getDeleted()) {
                                $sspace->getBuilding()->setDeleted(false);
                            }
                            $em->persist($sspace);
                        }
                    } else {
                        if (!$sspace->getDeleted()) {
                            $sspace->setDeleted(true);
                            $em->persist($sspace);
                        }
                    }
                }
            }
            
            // step 5: add new data
            
            // step 6: enable spaces
            
            // step 7: enable buildings
            
            // step 8: results
            //$em->flush();
            die('end');
            
            // return data
            $data = array('err'=>false);
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/       
    }
    
    public function configSaveAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }

            $name = $this->params()->fromPost('name', false);
            $name = empty($name)?null:$name;
            
            // hydrate the doctrine entity
            $save = $this->saveConfig($name);
            
            // return data
            $data = array('err'=>false, 'info'=>array (
                'saveId'=>$save->getSaveId(),
                'name'=>$save->getName(),
                'created'=>$save->getCreated()->format('jS F Y H:i:s')
            ));
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/        
    }
    
    /**
     * save config
     * @param type $name
     * @return \Project\Entity\Save
     * @throws \Project\Controller\Exception
     */
    private function saveConfig($name=null) {
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
                    . 'JOIN s.legacy l '
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
            
            // now compare checksums
            $qb = $em->createQueryBuilder();
            $qb
                ->select('s.checksum, s.saveId')
                ->from('Project\Entity\Save', 's')
                ->where('s.project = '.$this->getProject()->getProjectId())
                ->orderBy('s.created', 'DESC');

            $query  = $qb->getQuery();
            $query->setMaxResults(1);
            $item = $query->getSingleResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
            
            if (!empty($item)) {
                if ($item['checksum']==$save->getChecksum()) {
                    $save->setSaveId($item['saveId']);
                    return $save;
                }
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
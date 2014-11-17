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
    public function activityAction() {
        $this->setCaption('Activity');
    }
    
    public function indexAction()
    {
        $this->setCaption('Project Dashboard');
        
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT p.model, p.eca, pt.service, pt.name AS productType, s.ppu, '
                . 'SUM(s.quantity) AS quantity, '
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
                ->setAttribute('action', '/activity/add/')
                ->setAttribute('class', 'form-nomargin');
        
        $contacts = $this->getProject()->getContacts();
        
        $this->getView()
                ->setVariable('contacts', $contacts)
                ->setVariable('proposals', $proposals)
                ->setVariable('formActivity', $formActivity)
                ->setVariable('user', $this->getUser())
                ->setVariable('audit', $audit)
                ->setVariable('activities', $activities)
                ->setVariable('system', $system);
        
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
        $query = $this->getEntityManager()->createQuery("SELECT p.model, p.ppu, p.eca, p.pwr, p.productId, b.name as brand, t.name as type, t.service, t.typeId "
                . "FROM Product\Entity\Product p "
                . "JOIN p.brand b "
                . "JOIN p.type t "
                . "WHERE p.active = 1 "
                . "ORDER BY b.name ASC, p.model ASC");
        $products = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $query = $this->getEntityManager()->createQuery("SELECT l.legacyId, l.description, l.quantity, l.pwr_item, l.pwr_ballast, l.emergency, l.dim_item, l.dim_unit, c.maintenance, c.name as category, p.productId FROM Product\Entity\Legacy l JOIN l.category c LEFT JOIN l.product p ORDER BY l.category ASC, l.description ASC");
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
            
            // note issue arises here
            $spaces = $this->getEntityManager()->getRepository('Space\Entity\Space')->findByBuildingId($post['bid'], $this->getProject()->getProjectId(), true, array('agg'=>array('ppu'=>true, 'cpu'=>true, 'quantity'=>true)));
            
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
        $form = new SetupForm($this->getEntityManager(), $this->getProject()->getClient());
        $form->setAttribute('action', $this->getRequest()->getUri()); // set URI to current page
        
        $form->bind($this->getProject());
        $form->setBindOnValidate(true);
        
        if ($saveRequest) {
            try {
                if (!$this->getRequest()->isPost()) {
                    throw new \Exception('illegal method');
                }
                
                $post = $this->params()->fromPost();
                $form->setData($post);
                if ($form->isValid()) {
                    if (empty($post['states'])) {
                        $this->getProject()->setStates(new \Doctrine\Common\Collections\ArrayCollection());
                    }
                    if (empty($post['contacts'])) {
                        $this->getProject()->setContacts(new \Doctrine\Common\Collections\ArrayCollection());
                    }
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
            $form->get('name')->setAttribute('readonly', 'true');
            $this->getView()
                    ->setVariable('form', $form);
            return $this->getView();
        }
    }
    
    public function closeAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            $this->getProject()->setCancelled(true);
            $this->getEntityManager()->persist($this->getProject());
            $this->getEntityManager()->flush();
            
            $data = array('err'=>false);
            $this->AuditPlugin()->auditProject(203, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId());
            $this->flashMessenger()->addMessage(array(
                'The project has been marked as lost', 'Success!'
            ));
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    public function activateAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            $this->getProject()->setCancelled(false);
            $this->getEntityManager()->persist($this->getProject());
            $this->getEntityManager()->flush();
            
            $data = array('err'=>false);
            $this->AuditPlugin()->auditProject(204, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId());
            $this->flashMessenger()->addMessage(array(
                'The project has been re-activated successfully', 'Success!'
            ));
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }    
    
    
    public function signedAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            $em = $this->getEntityManager();
            
            $hydrator = new DoctrineHydrator($em,'Project\Entity\Project');
            $hydrator->hydrate(
                array (
                    'weighting'=>100,
                    'status'=>40
                ),
                $this->getProject());

            $em->persist($this->getProject());/**/            
            $this->getEntityManager()->flush();
            
            $data = array('err'=>false, 'url'=>'/client-'.$this->getProject()->getClient()->getClientId().'/job-'.$this->getProject()->getProjectId().'/');
            $this->AuditPlugin()->auditProject(205, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId());
            $this->flashMessenger()->addMessage(array(
                'The project upgraded to a job successfully', 'Success!'
            ));
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }    
    
    public function addPropertyAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }

            $post = $this->getRequest()->getPost();

            $form = new \Project\Form\BlueSheetOrderDateForm();
            $form->setInputFilter(new \Project\Filter\BlueSheetOrderDateFilter());

            $form->setData($post);
            if ($form->isValid()) {
                $projectProperty = new \Project\Entity\ProjectProperty();
                $property = $this->getEntityManager()->find('Application\Entity\Property', 20); // order date
                $dt = \DateTime::createFromFormat('d/m/Y', $post['OrderDate']);
                $projectProperty
                        ->setValue($dt->getTimestamp())
                        ->setProject($this->getProject())
                        ->setProperty($property);
                
                
                $this->getEntityManager()->persist($projectProperty);
                $this->getEntityManager()->flush();
                
                $data = array('err'=>false);
            } else {
                $data = array('err'=>true, 'info'=>$form->getMessages());
            }
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    public function competitorDeleteAction() {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception('Illegal request type');
            }
            
            if (!$this->getRequest()->isPost()) {
                throw new \Exception('illegal method');
            }

            $cid = $this->params()->fromPost('cid');
            if (empty($cid)) {
                throw new \Exception('competitor id not found');
            }
            
            foreach ($this->getProject()->getCompetitors() as $competitorLink) {
                if ($competitorLink->getCompetitor()->getCompetitorId() == $cid) {
                    $this->getEntityManager()->remove($competitorLink);
                }
            }

            $this->getEntityManager()->flush();
            
            $data = array('err'=>false);
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }

        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    public function competitorsaveAction() {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception('Illegal request type');
            }
            
            if (!$this->getRequest()->isPost()) {
                throw new \Exception('illegal method');
            }

            $cid = $this->params()->fromPost('cid');
            if (empty($cid)) {
                throw new \Exception('competitor id not found');
            }
            
            $competitor = $this->getEntityManager()->find('Application\Entity\Competitor', $cid);

            if (empty($competitor)) {
                throw new \Exception('competitor does not exist');
            }
            
            
            $post = $this->params()->fromPost();
            $weaknesses = array();
            if (!empty($post['weaknesses'])) {
                foreach ($post['weaknesses'] as $weakness) {
                    if (!empty($weakness)) {
                        $weaknesses[] = $weakness;
                    }
                }
            }
            
            $strengths = array();
            if (!empty($post['strengths'])) {
                foreach ($post['strengths'] as $strength) {
                    if (!empty($strength)) {
                        $strengths[] = $strength;
                    }
                }
            }
            
            $projectCompetitor = false;
            foreach ($this->getProject()->getCompetitors() as $competitorLink) {
                if ($competitorLink->getCompetitor()->getCompetitorId() == $cid) {
                    $projectCompetitor = $competitorLink;
                    break;
                }
            }

            if (empty ($projectCompetitor)) {
                $projectCompetitor = new \Project\Entity\ProjectCompetitor();
                $projectCompetitor
                        ->setProject($this->getProject())
                        ->setCompetitor($competitor);
            } elseif (!empty($post['add'])) { // if we have the add flag but already exists then do nothing
                throw new \Exception('Relationship already exists');
            }
            
            $projectCompetitor
                    ->setResponse(empty($post['response'])?null:$post['response'])
                    ->setStrategy(empty($post['strategy'])?null:$post['strategy'])
                    ->setStrengths(json_encode($strengths))
                    ->setWeaknesses(json_encode($weaknesses))
                ;
            
            $this->getEntityManager()->persist($projectCompetitor);
            $this->getEntityManager()->flush();
            
            $data = array('err'=>false, 'info' => array('name'=>$competitor->getName()));
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }

        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    public function competitorFindAction() {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception('Illegal request type');
            }
            
            if (!$this->getRequest()->isPost()) {
                throw new \Exception('illegal method');
            }
            
            
            $cid = $this->params()->fromPost('cid');
            
            $competitor = array();
            foreach ($this->getProject()->getCompetitors() as $competitorLink) {
                if ($competitorLink->getCompetitor()->getCompetitorId() == $cid) {
                    $competitor = array (
                        'cid'=>$competitorLink->getCompetitor()->getCompetitorId(),
                        'name'=>$competitorLink->getCompetitor()->getName(),
                        'url'=>!empty($competitorLink->getCompetitor()->getUrl())?'http://'.preg_replace('/^http:[\/]+/i','',$competitorLink->getCompetitor()->getUrl()):null,
                        'gStrengths'=>json_decode($competitorLink->getCompetitor()->getStrengths(), true),
                        'gWeaknesses'=>json_decode($competitorLink->getCompetitor()->getWeaknesses(), true),
                        'strengths'=>json_decode($competitorLink->getStrengths(), true),
                        'weaknesses'=>json_decode($competitorLink->getWeaknesses(), true),
                        'strategy'=>empty($competitorLink->getStrategy())?'':$competitorLink->getStrategy(),
                        'response'=>empty($competitorLink->getResponse())?'':$competitorLink->getResponse(),
                    );
                    
                    break;
                }
            }

            if (empty ($competitor)) {
                throw new \Exception('Item not found');
            }
            
            $data = array('err'=>false, 'info'=>$competitor);
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }

        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    
    
    public function blueSheetAction()
    {
        $saveRequest = ($this->getRequest()->isXmlHttpRequest());
        
        
        
        if ($saveRequest) {
            try {
                if (!$this->getRequest()->isPost()) {
                    throw new \Exception('illegal method');
                }
                
                $post = $this->params()->fromPost();
                $props = $this->getEntityManager()->getRepository('Application\Entity\Property')->findByGrouping(array(1, 2, 4, 16));

                
                
                
                $storedPropsLinks = array();
                foreach ($this->getProject()->getProperties() as $propertyLink) {
                    $storedPropsLinks[$propertyLink->getProperty()->getName()] = $propertyLink;
                }

                $em = $this->getEntityManager();

                $keywinresult = array();
                if (!empty($post['kwrcontactid'])) {
                    foreach ($post['kwrcontactid'] as $id=>$cid) {
                        if (empty($post['kwr'][$id])) {
                            continue;
                        }
                        $keywinresult[$cid] = array(
                            0=>$post['kwr'][$id],
                            1=>$post['kwrrating'][$id],
                        );
                    }
                }
                
                $post['BuyingInfluence'] = json_encode($keywinresult);
                
                $obj = new \Project\Entity\ProjectProperty();
                $obj->setProject($this->getProject());
                $obj->setProperty($prop);
                
                // save competitor information
                foreach ($props as $prop) {
                    if (!empty($post[$prop->getName()])) {
                        if (isset($storedPropsLinks[$prop->getName()])) { // already exists
                            $obj = $storedPropsLinks[$prop->getName()];
                            if ($obj->getValue() == $post[$prop->getName()]) {
                                continue;
                            }
                        } else { // create new
                            $obj = new \Project\Entity\ProjectProperty();
                            $obj->setProject($this->getProject());
                            $obj->setProperty($prop);
                        }

                        if (is_array($post[$prop->getName()])) {
                            $arr = array();
                            foreach ($post[$prop->getName()] as $value) {
                                if (!empty(trim($value))) {
                                    $arr[] = $value;
                                }
                            }
                            if (empty($arr)) {
                                $em->remove($obj);
                                continue;
                            } else {
                                $obj->setValue(json_encode($arr));
                            }
                        } else {
                            $obj->setValue($post[$prop->getName()]);
                        }

                        $em->persist($obj);
                    } else {
                        if (isset($storedPropsLinks[$prop->getName()])) {
                            $em->remove($storedPropsLinks[$prop->getName()]);
                        }
                    }
                }
                $em->flush();
                $data = array('err'=>false,);
                
            } catch (\Exception $ex) {
                $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
            }

            return new JsonModel(empty($data)?array('err'=>true):$data);/**/
        } else {
            $contacts = $this->getProject()->getContacts();
        
            $props['competition'] = $this->getEntityManager()->getRepository('Application\Entity\Property')->findByGrouping(1);
            $props['criteria'] = $this->getEntityManager()->getRepository('Application\Entity\Property')->findByGrouping(2);

            $storedPropsLinks = array();
            foreach ($this->getProject()->getProperties() as $propertyLink) {
                $storedPropsLinks[$propertyLink->getProperty()->getName()] = $propertyLink;
            }

            $this->setCaption('Blue Sheet');
            
            $competitorList = array();
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb
                ->select('c.name, c.competitorId')
                ->from('Application\Entity\Competitor', 'c');
        
            $query  = $qb->getQuery();
            $competitorsTmp = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
            $competitorList = array();
            foreach ($competitorsTmp as $data) {
                $competitorList[$data['competitorId']] = $data;
            }
            
            $qb = $this->getEntityManager()->createQueryBuilder();
            $qb
                ->select('c.competitorId')
                ->from('Project\Entity\ProjectCompetitor', 'pc')
                ->innerJoin('pc.competitor', 'c')
                ->where('pc.project = '.$this->getProject()->getProjectId())
                    ;

            $query  = $qb->getQuery();
            $exclude = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

            foreach ($exclude as $competitor) {
                if (isset($competitorList[$competitor['competitorId']])) {
                    unset ($competitorList[$competitor['competitorId']]);
                }
            }
                
            $competitors = $this->getProject()->getCompetitors();
            
            $formCompetitorAdd = new \Application\Form\CompetitorAddForm($this->getEntityManager());
            $formCompetitorAdd
                    ->setAttribute('action', '/competitor/add/')
                    ->setAttribute('class', 'form-horizontal');
            
            $formOrderDate = new \Project\Form\BlueSheetOrderDateForm();
            $formOrderDate
                    ->setAttribute('action', '/client-'.$this->getProject()->getClient()->getClientId().'/project-'.$this->getProject()->getProjectId().'/addproperty/')
                    ->setAttribute('class', 'form-horizontal');
                    

            if (isset($storedPropsLinks['OrderDate'])) {
                $formOrderDate->get('OrderDate')->setValue(date('d/m/Y', $storedPropsLinks['OrderDate']->getValue()));
            }
            
            $this->getView()
                    ->setVariable('formOrderDate',$formOrderDate)
                    ->setVariable('formCompetitorAdd',$formCompetitorAdd)
                    ->setVariable('competitorList', $competitorList)
                    ->setVariable('competitors', $competitors)
                    ->setVariable('storedProps', $storedPropsLinks)
                    ->setVariable('props', $props)
                    ->setVariable('contacts', $contacts);
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
            
            if ($save->getProject()->getProjectId() != $this->getProject()->getProjectId()) {
                throw new \Exception('Project does not match');
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
            
            
            // step 6: set activated date
            $save->setActivated(new \DateTime());
            $em->persist($save);
            
            // step 7: results
            $em->flush();
            
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
    
    

    
    public function fileManagerUploadAction() {
        $storeFolder = '/Users/jonnycook/ZendProjects/projects/8point3upload/';
        if (!empty($_FILES)) {
            try {
                $tempFile = $_FILES['file']['tmp_name'];          //3             
                $targetPath = $storeFolder;  //4
                $targetFile =  $targetPath. $_FILES['file']['name'];  //5
                
                if (!move_uploaded_file($tempFile,$targetFile)) {
                    throw new \Exception('bugger');
                } //6/**/
            } catch (\Exception $e) {
                $this->AuditPlugin()->auditProject(202, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId(), array('data'=>array('failed = '.$e->getMessage().' - '.date('Y-m-d H:i:s'))));
            }
        } else {                                                           
            $result  = array();

            $files = scandir($storeFolder);                 //1
            if ( false!==$files ) {
                foreach ( $files as $file ) {
                    if ( '.'!=$file && '..'!=$file) {       //2
                        $obj['name'] = $file;
                        $obj['size'] = filesize($storeFolder.$ds.$file);
                        $result[] = $obj;
                    }
                }
            }

            header('Content-type: text/json');              //3
            header('Content-type: application/json');
            echo json_encode($result);
        }
        die();
    }
    
    public function fileManagerRetrieveAction() {
        $file = $this->params()->fromQuery('file');
        $storeFolder = '/Users/jonnycook/ZendProjects/projects/8point3upload/';
        header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
        header("Cache-Control: public"); // needed for i.e.
        header("Content-Type: image/png");
        header("Content-Transfer-Encoding: Binary");
        header("Content-Length:".filesize($storeFolder.$file));
        readfile($storeFolder.$file);
        die();        

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
                $this->flashMessenger()->addMessage(array('The project note has been added successfully', 'Success!'));
            } 

            $data = array('err'=>false, 'cnt'=>$noteCnt, 'id'=>$noteIdx);
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    function collaboratorsAction() {
        $saveRequest = ($this->getRequest()->isXmlHttpRequest());
        
        $form = new \Project\Form\CollaboratorsForm($this->getEntityManager());
        $form
            ->setAttribute('class', 'form-horizontal')
            ->setAttribute('action', '/client-'.$this->getProject()->getClient()->getClientId().'/project-'.$this->getProject()->getProjectId().'/collaborators/');

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
    
    function emailAction() {
        $this->setCaption('Project Emails');
        $form = new \Project\Form\EmailForm();
        
        $recipients = array(
            'client' => array (
                'label' => 'CLIENT CONTACTS',
                'options' => array (),
            ),
            'projis' => array (
                'label' => 'PROJIS CONTACTS',
                'options' => array (),
            ),
        );
        $contacts = $this->getEntityManager()->getRepository('Contact\Entity\Contact')->findByClientId($this->getProject()->getClient()->getclientId());
        foreach ($contacts as $contact) {
            $recipients['client']['options'][$contact->getEmail()] = $contact->getForename().' '.$contact->getSurname();
        }
        
        $users = $this->getEntityManager()->getRepository('Application\Entity\User')->findByCompany($this->getUser()->getCompany()->getCompanyId());
        foreach ($users as $user) {
            $recipients['projis']['options'][$user->getEmail()] = $user->getName();
        }
        
        
        $form->get('to')->setAttribute('options', $recipients);
        $form->get('cc')->setAttribute('options', $recipients);
        
        $form->setAttribute('class', 'form-horizontal');
        $this->getView()
                    ->setVariable('form', $form)
                    ;

        return $this->getView();
    }
    
    function emailThreadAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            $googleService = $this->getGoogleService();
        
            if (!$googleService->hasGoogle()) {
                die ('the service is not enabled for this user');
            }
            $googleService->setProject($this->getProject());
            $mail = $googleService->findGmailThreads(array (), false);
            
            return new JsonModel(array('err'=>false, 'mail'=>$mail));/**/
        } catch (\Exception $e) {
            return new JsonModel(array('err'=>true, 'info'=>$e->getMessage()));/**/
        }
    }
    
}
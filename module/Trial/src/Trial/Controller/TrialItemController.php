<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Trial\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

class TrialitemController extends TrialSpecificController
{
    
    public function indexAction()
    {
        $this->setCaption('Trial Dashboard');
        
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT p.model, p.eca, pt.service, pt.name AS productType, s.ppu, s.ppuTrial, pt.typeId, '
                . 'SUM(s.quantity) AS quantity, '
                . 'SUM(s.ppuTrial*s.quantity) AS price '
                . 'FROM Space\Entity\System s '
                . 'JOIN s.space sp '
                . 'JOIN s.product p '
                . 'JOIN p.type pt '
                . 'WHERE sp.project='.$this->getProject()->getProjectId().' '
                . 'GROUP BY s.product '
                . 'ORDER BY p.type');
        $systems = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        
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
                ->setVariable('formActivity', $formActivity)
                ->setVariable('user', $this->getUser())
                ->setVariable('audit', $audit)
                ->setVariable('activities', $activities)
                ->setVariable('systems', $systems);
        
		return $this->getView();
        
    }
    
    
    public function setupAction()
    {
        $saveRequest = ($this->getRequest()->isXmlHttpRequest());
        
        $this->setCaption('Trial Configuration');
        $form = new \Trial\Form\SetupForm($this->getEntityManager(), $this->getProject()->getClient());
        $form->setAttribute('action', $this->getRequest()->getUri()); // set URI to current page

        $form->bind($this->getProject());
        $form->setBindOnValidate(true);

        if ($saveRequest) {
            try {
                if (!$this->getRequest()->isPost()) {
                    throw new \Exception('illegal method');
                }
                
                $additional = array (
                    'weighting'=>0,
                    'type'=>3,
                    'mcd'=>0,
                    'eca'=>0,
                    'carbon'=>0,
                    'ibp'=>0,
                    'financeYears'=>0
                );
                $post = $this->params()->fromPost()+$additional;
                //$form->setInputFilter(new \Trial\Filter\SetupFilter());
                if (!empty($post['installed'])) {
                    $dt = \DateTime::createFromFormat('d/m/Y', $post['installed']);
                    $this->getProject()->setInstalled($dt);
                }/**/
                unset($post['installed']);
                
                if (!empty($post['completed'])) {
                    $dt = \DateTime::createFromFormat('d/m/Y', $post['completed']);
                    $this->getProject()->setCompleted($dt);
                }/**/
                unset($post['completed']);
                
                $form->setData($post);
                if ($form->isValid()) {
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
            if (!empty($this->getProject()->getInstalled())) {
                $form->get('installed')->setValue($this->getProject()->getInstalled()->format('d/m/Y'));
            }
            if (!empty($this->getProject()->getCompleted())) {
                $form->get('completed')->setValue($this->getProject()->getCompleted()->format('d/m/Y'));
            }
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
            $this->AuditPlugin()->auditProject(208, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId());
            $this->flashMessenger()->addMessage(array(
                'The trial has been marked as cancelled', 'Success!'
            ));
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    
    public function startAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            
            $post = $this->params()->fromPost();
            
            $form = new \Trial\Form\StartDateForm();
            $form->setInputFilter(new \Trial\Filter\StartDateFilter());
            $form->setData($post);
            if ($form->isValid()) {
                $this->getProject()->setStatus($this->getEntityManager()->find('Project\Entity\Status', 45));
                $this->getProject()->setCancelled(false);
                $dt = \DateTime::createFromFormat('d/m/Y', $form->get('installed')->getValue());
                $this->getProject()->setInstalled($dt);

                
                $this->getEntityManager()->persist($this->getProject());
                $this->getEntityManager()->flush();
                $data = array('err'=>false);
                $this->AuditPlugin()->auditProject(207, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId());
                $this->flashMessenger()->addMessage(array(
                    'The trial has been activated', 'Success!'
                ));
            } else {
                $data = array('err'=>true, 'info'=>$form->getMessages());
            }
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    
    public function completedAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            
            $post = $this->params()->fromPost();
            
            $form = new \Trial\Form\StartDateForm();
            $form->setInputFilter(new \Trial\Filter\StartDateFilter());
            if (!empty($post['completed'])) {
                $post['installed'] = $post['completed'];
            }
            $form->setData($post);
            if ($form->isValid()) {
                $this->getProject()->setStatus($this->getEntityManager()->find('Project\Entity\Status', 100));
                $this->getProject()->setCancelled(false);
                $dt = \DateTime::createFromFormat('d/m/Y', $form->get('installed')->getValue());
                $this->getProject()->setCompleted($dt);

                
                $this->getEntityManager()->persist($this->getProject());
                $this->getEntityManager()->flush();
                $data = array('err'=>false);
                $this->AuditPlugin()->auditProject(209, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId());
                $this->flashMessenger()->addMessage(array(
                    'The trial has been completed', 'Success!'
                ));
            } else {
                $data = array('err'=>true, 'info'=>$form->getMessages());
            }
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
            ->setAttribute('action', '/client-'.$this->getProject()->getClient()->getClientId().'/trial-'.$this->getProject()->getProjectId().'/collaborators/');

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
        
        // get product information
        $query = $this->getEntityManager()->createQuery("SELECT p.model, p.ppu, p.ppuTrial, p.eca, p.pwr, p.productId, b.name as brand, t.name as type, t.service, t.typeId "
                . "FROM Product\Entity\Product p "
                . "JOIN p.brand b "
                . "JOIN p.type t "
                . "WHERE p.active = 1 "
                . "ORDER BY b.name ASC, p.model ASC");
        $products = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $query = $this->getEntityManager()->createQuery("SELECT l.legacyId, l.description, l.quantity, l.pwr_item, l.pwr_ballast, l.emergency, l.dim_item, l.dim_unit, c.maintenance, c.name as category, p.productId FROM Product\Entity\Legacy l JOIN l.category c LEFT JOIN l.product p ORDER BY l.category ASC, l.description ASC");
        $legacies = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        $systems=$this->getEntityManager()->getRepository('Space\Entity\System')->findBySpaceId($space->getSpaceId(), array('array'=>true));
        
        // system create form
        $formSystem = new \Space\Form\SpaceAddProductForm($this->getEntityManager());
        $formSystem->setAttribute('class', 'form-horizontal');
        $formSystem->setAttribute('action', '/client-'.$this->getProject()->getClient()->getClientId().'/project-'.$this->getProject()->getProjectId().'/space-'.$space->getSpaceId().'/addsystem/');
        
        $system = new \Space\Entity\System();
        $formSystem->bind($system);

        
        $this->getView()
            ->setVariable('formSystem', $formSystem)
            ->setVariable('space', $space)
            ->setVariable('products', $products)
            ->setVariable('legacies', $legacies)
            ->setVariable('systems', $systems);
        
		return $this->getView();
    }
    
    
    public function serialsAction()
    {
        
        $this->setCaption('Serial Management');
        
        $em = $this->getEntityManager();
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder
            ->select('s')
            ->from('Job\Entity\Serial', 's')
            ->where('s.project = :projectId')
            ->setParameter("projectId", $this->getProject()->getProjectId());
        
        $query = $queryBuilder->getQuery();
        $serials = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        $this->debug()->dump($serials);
        die('boosh');
        
		return $this->getView();
    }
    
}

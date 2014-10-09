<?php
namespace Space\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Json\Json;

use Application\Controller\AuthController;

use Zend\Mvc\MvcEvent;

use Zend\View\Model\JsonModel;

class SpaceitemController extends SpaceSpecificController
{
    
    public function indexAction()
    {
        /*$em = $this->getEntityManager();
        $filename = 'jonny.doc';
        $ext = preg_replace('/^[^.]+[.]([^.]+)$/','$1',$filename);
        
        $queryBuilder = $em->createQueryBuilder();
        $queryBuilder
            ->select('d')
            ->from('Project\Entity\DocumentExtension', 'd')
            ->where('d.extension=?1')
            ->setParameter(1, $ext);

        $query  = $queryBuilder->getQuery();
        try {
            $extObjs = $query->getResult();
            echo $extObj->getExtension(),' found';
        } catch (\Exception $e) {
            echo 'err';
            echo $e->getMessage();
        }
        die('moo');/**/
        $this->setCaption('Space: '.$this->getSpace()->getName());
        
        $formSystem = new \Space\Form\SpaceAddProductForm($this->getEntityManager());
        $formSystem->setAttribute('class', 'form-horizontal');
        $formSystem->setAttribute('action', '/client-'.$this->getProject()->getClient()->getClientId().'/project-'.$this->getProject()->getProjectId().'/space-'.$this->getSpace()->getSpaceId().'/addsystem/');
        $system = new \Space\Entity\System();
        $formSystem->bind($system);
        
        $formSpace = new \Space\Form\SpaceCreateForm($this->getEntityManager(), $this->getProject()->getClient()->getClientId());
        $formSpace->setAttribute('class', 'form-horizontal');
        $formSpace->setAttribute('action', '/client-'.$this->getProject()->getClient()->getClientId().'/project-'.$this->getProject()->getProjectId().'/space-'.$this->getSpace()->getSpaceId().'/update/');
        $formSpace->bind($this->getSpace());
        
        $query = $this->getEntityManager()->createQuery("SELECT p.model, p.ppu, p.eca, p.pwr, p.productId, b.name as brand, t.name as type, t.service, t.typeId "
                . "FROM Product\Entity\Product p "
                . "JOIN p.brand b "
                . "JOIN p.type t "
                . "WHERE p.active = 1 "
                . "ORDER BY b.name ASC, p.model ASC");
        $products = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $query = $this->getEntityManager()->createQuery("SELECT l.legacyId, l.description, l.quantity, l.pwr_item, l.pwr_ballast, l.emergency, l.dim_item, l.dim_unit, c.maintenance, c.name as category FROM Product\Entity\Legacy l JOIN l.category c ORDER BY l.category ASC, l.description ASC");
        $legacies = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        
        $systems=$this->getEntityManager()->getRepository('Space\Entity\System')->findBySpaceId($this->getSpace()->getSpaceId(), array('array'=>true));
        
        $this->getView()
             ->setVariable('formSystem', $formSystem)
             ->setVariable('formSpace', $formSpace)
             ->setVariable('products', $products)
             ->setVariable('legacies', $legacies)
             ->setVariable('systems', $systems);
        
		return $this->getView();
    }
    
    
    /**
     * Add or modify new space action metho
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function addSystemAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            
            $post = $this->getRequest()->getPost();
            $addMode = empty($post['systemId']);

            $form = new \Space\Form\SpaceAddProductForm($this->getEntityManager());
            if ($addMode) {
                $system = new \Space\Entity\System();
            } else {
                $system = $this->getEntityManager()->find('Space\Entity\System', $post['systemId']);
                if (!($system instanceof \Space\Entity\System)) {
                    throw new \Exception('could not retrieve space object');
                }
                
                if ($system->getSpace()->getSpaceId() != $this->getSpace()->getSpaceId()) {
                    throw new \Exception('space mismatch');
                }
                
            }
            $form->bind($system);
            $form->setBindOnValidate(true);
            
            // check for architectural
            if (!empty($post['qMode'])) {
                $productId = $this->params()->fromPost('product', false);
                $length = $this->params()->fromPost('length', false);
                $mode = 1;
            
                if (empty($productId) || !preg_match('/^[\d]+$/', $productId)) {
                    return new JsonModel(array('err'=>true, 'info'=>array(
                        'product'=>array("isEmpty"=>"Value is required and can't be empty")
                    )));
                }

                if (empty($length) || !preg_match('/^[\d]+(.[\d]+)?$/', $length)) {
                    return new JsonModel(array('err'=>true, 'info'=>array(
                        'length'=>array("isEmpty"=>"Value is required and can't be empty")
                    )));
                }
            
                // find product cost per unit
                $product = $this->getEntityManager()->find('Product\Entity\Product', $productId);
                if (!($product instanceof \Product\Entity\Product)) {
                    return new JsonModel(array('err'=>true, 'info'=>array(
                        'product'=>array("isEmpty"=>"Product not found on system")
                    )));
                }
            
                if ($product->getType()->getTypeId() != 3) { // architectural
                    return new JsonModel(array('err'=>true, 'info'=>array(
                        'product'=>array("isEmpty"=>"Product is not architectural")
                    )));
                }
            
                $attributes = $this->getServiceLocator()->get('Model')->findOptimumArchitectural($product, $length, $mode);
                
                $post['quantity'] = $attributes['dBillU'];
                // we only want some of the attributes
                $attributes = array_intersect_key($attributes, array(
                    'dLen'=>true,
                    'dConf'=>true,
                    'sLen'=>true,
                ));
                
            }
            
            $form->setData($post);

            if ($form->isValid()) {
                if (!empty($form->get('legacy')->getValue())) {
                    $errs = array();
                    if (empty($form->get('legacyQuantity')->getValue())) {
                        $errs['legacyQuantity'] = array('If a legacy product is selected then the quantity must be greater than 0');
                        
                    }
                    
                    if (empty($form->get('legacyWatts')->getValue())) {
                        $errs['legacyWatts'] = array('If a legacy product is selected then the rating must be greater than 0');
                    }
                    
                    if (!empty($errs)) {
                        return new JsonModel(array('err'=>true, 'info'=>$errs));
                    }
                    
                }

                $form->bindValues();
                if ($addMode) {
                    $system->setSpace($this->getSpace());
                }
                $system->setCpu($system->getProduct()->getCpu());
                
                if (isset($attributes)) {
                    $system->setAttributes($attributes);
                }
                
                $this->getEntityManager()->persist($system);
                $this->getEntityManager()->flush();
                    
                $this->flashMessenger()->addMessage(array(
                    'The product &quot;'.$system->getProduct()->getModel().'&quot; has been '.($addMode?'added':'modified').' successfully', 'Success!'
                ));
                    
                $data = array('err'=>false, 'info'=>array(
                    'systemId' => $system->getSystemId()
                ));
                
                $this->synchroniseInstallation();
                
                $this->AuditPlugin()->auditSpace($addMode?304:306, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId(), $this->getSpace()->getSpaceId(), array(
                    'product'=>$system->getProduct()->getProductId()
                ));
                
            } else {
                $data = array('err'=>true, 'info'=>$form->getMessages());
            }/**/
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }    
    
    /**
     * synchronize installation ppu
     * @return boolean
     * @throws \Exception
     */
    private function synchroniseInstallation () {
        try {
            $query = $this->getEntityManager()->createQuery("SELECT SUM(s.ippu * s.quantity) AS price FROM Space\Entity\System s WHERE s.space = {$this->getSpace()->getSpaceId()}");
            $sum = $query->getSingleScalarResult();
            $sum = round($sum, 2);
            $systems=$this->getEntityManager()->getRepository('Space\Entity\System')->findBySpaceId($this->getSpace()->getSpaceId(), array('locked'=>true,'type'=>100));
            if (!empty($systems)) {
                $systemInstall = array_shift($systems);
            }

            if (empty($sum)) {
                if (!empty($systemInstall)) {
                    $this->getEntityManager()->remove($systemInstall);
                    $this->getEntityManager()->flush();
                } 
            } else {
                if (empty($systemInstall)) {
                    $products=$this->getEntityManager()->getRepository('Product\Entity\Product')->findByType(100);
                    if (empty($products)) {
                        throw new \Exception('Could not find installation product');
                    }
                    $product = array_shift($products);
                    $systemInstall = new \Space\Entity\System();
                    $systemInstall
                            ->setSpace($this->getSpace())
                            ->setQuantity(1)
                            ->setIppu(0)
                            ->setHours(0)
                            ->setLux(0)
                            ->setOccupancy(0)
                            ->setLocked(true)
                            ->setLegacy(null)
                            ->setProduct($product);
                } else {
                    if ($systemInstall->getPpu()==$sum) {
                        return true;
                    }
                }

                $systemInstall
                        ->setCpu($sum)
                        ->setPpu($sum);
                $this->getEntityManager()->persist($systemInstall);
                $this->getEntityManager()->flush();
            }
            
            return true;

        } catch (\Exception $ex) {
            return false;
        }
    }
    
    
    /**
     * update space details
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function updateAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            $post = $this->getRequest()->getPost();
            $form= new \Space\Form\SpaceCreateForm($this->getEntityManager(), $this->getProject()->getClient()->getClientId());
            $form->bind($this->getSpace());
            $form->setBindOnValidate(true);
            $form->setData($post);
            
            if ($form->isValid()) {
                $form->bindValues();
                $this->getEntityManager()->persist($this->getSpace());
                $this->getEntityManager()->flush();
                    
                    
                $data = array('err'=>false);
                $this->AuditPlugin()->auditSpace(303, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId(), $this->getSpace()->getSpaceId());
            } else {
                $data = array('err'=>true, 'info'=>$form->getMessages());
            }/**/
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }    
    
    
    /**
     * Add note to space
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
            
            $notes = $this->getSpace()->getNotes();
            $notes = json_decode($notes, true);
            if (empty($notes)) {
                $notes = array();
            }
            
            $noteIdx = time();
            $notes[$noteIdx] = $note;
            $noteCnt = count($notes);
            $notes = json_encode($notes);
            
            $this->getSpace()->setNotes($notes);
            $this->getEntityManager()->persist($this->getSpace());
            $this->getEntityManager()->flush();

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
            
            $notes = $this->getSpace()->getNotes();
            $notes = json_decode($notes, true);
            
            if (!empty($notes[$noteId])) {
                unset($notes[$noteId]);
                $notes = json_encode($notes);
                $this->getSpace()->setNotes($notes);
                $this->getEntityManager()->persist($this->getSpace());
                $this->getEntityManager()->flush();
            }
            
            $data = array('err'=>false);
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    
    /**
     * retrieve system details
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function retrieveSystemAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            
            $post = $this->getRequest()->getPost();
            $systemId = $post['sid'];
            
            $errs = array();
            if (empty($systemId)) {
                throw new \Exception('note identifier not found');
            }
            
            $systems = $this->getEntityManager()->getRepository('Space\Entity\System')->findBySystemId($systemId, array('array'=>true));
            if (empty($systems)) {
                throw new \Exception('system not found');
            }
            
            $system = array_shift($systems);
            
            if (!empty($system['attributes'])) {
                $system['attributes'] = json_decode($system['attributes'], true);
            }
            
            if ($this->getSpace()->getSpaceId() != $system['spaceId']) {
                throw new \Exception('system does not belong to space');
            }
            
            $data = array('err'=>false, 'system'=>$system);
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    /**
     * delete system details
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function deleteSystemAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            $post = $this->getRequest()->getPost();
            $systemId = $post['sid'];
            
            $errs = array();
            if (empty($systemId)) {
                throw new \Exception('note identifier not found');
            }
            
            $system = $this->getEntityManager()->find('Space\Entity\System', $systemId);
            if (empty($system)) {
                throw new \Exception('system not found');
            }
            
            if ($this->getSpace()->getSpaceId() != $system->getSpace()->getSpaceId()) {
                throw new \Exception('system does not belong to space');
            }
            
            $productId = $system->getProduct()->getProductId();
            
            $this->getEntityManager()->remove($system);
            $this->getEntityManager()->flush();
            
            $this->flashMessenger()->addMessage(array(
                'The system product entry has been successfully deleted', 'Success!'
            ));
            
            $data = array('err'=>false);
            
            $this->synchroniseInstallation();
            
            $this->AuditPlugin()->auditSpace(305, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId(), $this->getSpace()->getSpaceId(), array(
                'product'=>$productId
            ));
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    /**
     * calculate architectural values 
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function architecturalCalculateAction() {
        try {
            
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            $post = $this->getRequest()->getPost();
            
            // test values
            $productId = $this->params()->fromPost('productId', false);
            $length = $this->params()->fromPost('length', false);
            $mode = 1;
            
            if (empty($productId) || !preg_match('/^[\d]+$/', $productId)) {
                throw new \Exception('illegal product parameter');
            }
            
            if (empty($length) || !preg_match('/^[\d]+(.[\d]+)?$/', $length)) {
                throw new \Exception('illegal product parameter');
            }
            
            // find product cost per unit
            $product = $this->getEntityManager()->find('Product\Entity\Product', $productId);
            if (!($product instanceof \Product\Entity\Product)) {
                throw new \Exception('illegal product selection');
            }
            
            if ($product->getType()->getTypeId() != 3) { // architectural
                throw new \Exception('illegal product type');
            }
            
            $data = $this->getServiceLocator()->get('Model')->findOptimumArchitectural($product, $length, $mode);
            
            $data = array('err'=>false, 'info'=>$data);
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }

   
    
}
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
        $this->setCaption('Space: '.(!empty($this->getSpace()->getName())?$this->getSpace()->getName():'Unnamed'));
        
        $q = $this->getEntityManager()->createQuery('SELECT s.spaceId, s.name FROM Space\Entity\Space s WHERE s.project=:project AND s.deleted!=true AND s.root=0 ORDER BY s.name ASC')
                ->setParameters(array('project' => $this->getProject()->getProjectId()));
        $result = $q->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        $spacePrev = false;
        $spaceNext = false;
        foreach ($result as $idx=>$space) {
            if ($space['spaceId']==$this->getSpace()->getSpaceId()) {
                if ($idx>0) {
                    $spacePrev = $result[$idx-1];
                }
                
                if (isset($result[$idx+1])) {
                    $spaceNext = $result[$idx+1];
                }
                
                break;
            }
        }
        
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

        $query = $this->getEntityManager()->createQuery("SELECT l.legacyId, l.description, l.quantity, l.pwr_item, l.pwr_ballast, l.emergency, l.dim_item, l.dim_unit, c.maintenance, c.name as category, p.productId FROM Product\Entity\Legacy l JOIN l.category c LEFT JOIN l.product p ORDER BY l.category ASC, l.description ASC");
        $legacies = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
        
        
        $systems=$this->getEntityManager()->getRepository('Space\Entity\System')->findBySpaceId($this->getSpace()->getSpaceId(), array('array'=>true));
        
        $this->getView()
             ->setVariable('spaceNext', $spaceNext)
             ->setVariable('spacePrev', $spacePrev)
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
                    
                } else {
                    $system->setLegacy(null);
                }

                $form->bindValues();
                
                if ($addMode) {
                    //$system->setPricing(null);
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
                
                $this->synchroniseInstallation($system->getProduct()->getProductId());
                
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
    private function synchroniseInstallation ($productId=false) {
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
            
            // synchronize product price point according to quantities
            if (!empty($productId)) {
                $product = $this->getEntityManager()->find('Product\Entity\Product', $productId);
                if (!$product instanceof \Product\Entity\Product) {
                    throw new \Exception('Product could not be found');
                }
                
                if (!$product->getType()->getService()) {
                    // find total number of items
                    $query = $this->getEntityManager()->createQuery("SELECT SUM(s.quantity) AS products FROM Space\Entity\System s JOIN s.space sp WHERE sp.project = {$this->getProject()->getProjectId()} AND s.product = {$productId}");
                    $sum = $query->getSingleScalarResult();

                    if (!empty($sum) && (count($product->getPricepoints())>0)) {
                        $ppu = $product->getppu();
                        $cpu = $product->getcpu();
                        $pricing_id = 'NULL';

                        foreach($product->getPricepoints() as $pricing) {
                            if (($sum>=$pricing->getMin()) && ($sum<=$pricing->getMax())) {
                                $ppu = $pricing->getppu();
                                $cpu = $pricing->getcpu();
                                $pricing_id = $pricing->getPricingId();
                                break;
                            }
                        }


                        $sql = "UPDATE `System` s "
                                . "INNER JOIN `Space` sp ON sp.`space_id` = s.`space_id` "
                                . "SET s.`cpu`={$cpu}, s.ppu={$ppu}, s.`pricing_id`= {$pricing_id} "
                                . "WHERE sp.`project_id`={$this->getProject()->getProjectId()} AND s.`product_id`={$productId}";

                        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
                        $stmt->execute();
                    }
                }
            }
            
            return true;

        } catch (\Exception $ex) {
            throw $ex;

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
            
            $this->synchroniseInstallation($productId);
            
            $this->AuditPlugin()->auditSpace(305, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId(), $this->getSpace()->getSpaceId(), array(
                'product'=>$productId
            ));
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    /**
     * copy system details
     * @return \Zend\View\Model\JsonModel
     * @throws \Exception
     */
    public function copySystemAction() {
        try {
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            $em = $this->getEntityManager();
            
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
            
            // now duplicate
            $systemNew = new \Space\Entity\System();
            $info = $system->getArrayCopy();
            unset($info['inputFilter']);
            unset($info['systemId']);
            

            $systemNew->populate($info);
            $em->persist($systemNew);              
            $em->flush();
            $systemId = $systemNew->getSystemId();
            
            
            $this->flashMessenger()->addMessage(array(
                'The system product entry has been successfully duplicated', 'Success!'
            ));
            
            $data = array('err'=>false);
            
            $this->synchroniseInstallation($system->getProduct()->getProductId());
            
            $this->AuditPlugin()->auditSpace(304, $this->getUser()->getUserId(), $this->getProject()->getClient()->getClientId(), $this->getProject()->getProjectId(), $this->getSpace()->getSpaceId(), array(
                'product'=>$productId
            ));
            
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
 
   
    
}
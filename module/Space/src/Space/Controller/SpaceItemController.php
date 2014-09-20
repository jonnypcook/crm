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
            
            // setup data
            $data = array(
                'deliverableLength'=>0,
                'deliverableBillable'=>0,
                'deliverableBillableUnits'=>0,
                'deliverableCost'=>0,
                'deliverableConfig'=>0,
            );
            
            $curLen = 0;
            $RemotePhosphorMax = 1800; // this is a moveable target- NEED TO CLARIFY
            $maxunitlength = 5000;  // this is a moveable target- NEED TO CLARIFY
            $fplRange = 50; // fewest phosphor lengths range

            $BoardA = 288.25;
            $BoardB = 286.75;
            $BoardB1 = 104.60;
            $BoardC = 288.35;

            $BoardGap = 1;
            $BoardEC = 2;

            $midBoardTypes = array (
                'B'=>$BoardB,
            );

            $configs = array();
            $startLen = $BoardEC + $BoardA + $BoardEC;
            $configs['A'] = array ($startLen, 'A', false);
            $maximum = 0;
            
            foreach ($midBoardTypes as $boardName=>$boardLength) {
                $boards = $midBoardTypes;
                unset($boards[$boardName]);

                $this->architecturalIterate($startLen, 'A', $boardLength, $boardName, $boards, $RemotePhosphorMax, $BoardGap, $BoardC, $BoardB1, $configs, $maximum);
            }
            
            $data['specifiedLength'] = $length;
            $data['maxBoardPerRP'] = $configs[$maximum][0];
            $data['maxBoardPerRPB'] = $maximum;
            $data['maximumUnitLength'] = $maxunitlength;
            
            
            // work out the maximum length
            $maximumCnt = floor($data['maximumUnitLength']/$configs[$maximum][0]);
            $remainder = $data['maximumUnitLength'] - ($maximumCnt * $configs[$maximum][0]);

            $optimumConfig = array($maximum=>$maximumCnt);

            $chosenRem = 0;
            // work out optimum configuration for remainder
            foreach ($configs as $type=>$length) {
                if ($length[0]<=$remainder) {
                    if (empty($chosenRem)) {
                        $chosenRem = $type;
                    } elseif ($length[0]>$configs[$chosenRem][0]) {
                        $chosenRem = $type;
                    }
                }
            }

            if (!empty($chosenRem)) {
                if (!empty($optimumConfig[$chosenRem])) {
                    $optimumConfig[$chosenRem]++;
                } else {
                    $optimumConfig[$chosenRem] = 1;
                }
            }
            
            // optimum length is the optimum length achievable
            $data['remotePhosphorMax'] = $RemotePhosphorMax;
            $data['optimumConfig'] = $optimumConfig;
            $data['optimumLength'] = 0;
            foreach ($optimumConfig as $type=>$cnt) {
                $data['optimumLength']+=$configs[$type][0] * $cnt;
            }

            // calculate the number of optimum lengths in required length
            $setup = array();
            $fullLengths = floor($data['specifiedLength']/$data['optimumLength']);
            $data['deliverableLength'] = $fullLengths * $data['optimumLength'];
            $remainder = $data['specifiedLength'] - ($fullLengths * $data['optimumLength']);

            // can't have a remainder that 
            if ($remainder<$configs['A']) {
            }

            //echo '<pre>',   print_r($optimumConfig, true),'</pre>';
            for ($i=0; $i<$fullLengths; $i++) {
                $setup[] = $optimumConfig;
            }

            // now work out optimum configuration for remainder
            $csetup = array();
            $this->architecturalFindLength($configs, $remainder, array(), 0, 0, $csetup);

            $tmpClosestIdx = false;
            if (!empty($csetup)) {
                foreach ($csetup as $idx=>$csData) {
                    if ($tmpClosestIdx ===false) {
                        $tmpClosestIdx = $idx;
                    } elseif ($csetup[$tmpClosestIdx][0]<$csData[0]) {
                        $tmpClosestIdx = $idx;
                    }
                }

                if ($mode==1) { // closest length mode
                    $data['deliverableLength']+=$csetup[$tmpClosestIdx][0];
                    $setup[] = $csetup[$tmpClosestIdx][1];
                } else {
                    $tmpClosestIdx2 = $tmpClosestIdx;
                    $tmpIteration = $csetup[$tmpClosestIdx][2];
                    foreach ($csetup as $idx=>$csData) {
                        if (($csetup[$tmpClosestIdx][0]-$csData[0]) <= $fplRange) {
                            if ($tmpIteration>$csData[2]) {
                                $tmpClosestIdx2=$idx;
                            } elseif ($tmpIteration==$csData[0]) {
                                if ($csetup[$tmpClosestIdx2][0]<$csData[0]) {
                                    $tmpClosestIdx2=$idx;
                                }
                            }
                        } 
                    }
                    $data['deliverableLength']+=$csetup[$tmpClosestIdx2][0];
                    $setup[] = $csetup[$tmpClosestIdx2][1];
                }
            }
            $data['deliverableBillableUnits'] = ceil($data['deliverableLength']/1000);
            $data['deliverableBillable'] = $data['deliverableBillableUnits'] * 1000;
            $data['deliverableCost'] = $data['deliverableBillableUnits'] * $product->getPPU();
            $data['deliverableConfig'] = $setup;

            //echo 'Number of full length units = ',floor($data['specifiedLength']/$data['optimumLength']), '<br>';
            //echo '<pre>',   print_r($setup, true),'</pre>'; die();
            //die();
                
            $data = array('err'=>false, 'info'=>$data);
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    function architecturalIterate($curLen, $currConf, $boardLen, $boardName, $boards, $maxlen, $boardGap, $boardC, $boardB1, array &$config, &$maximum) {
        $len = ($curLen+$boardGap+$boardC);
        $conf = $currConf.'-C';
        if ($len < $maxlen) {
            $config[$conf] = array ($len, $conf, true);
            if (empty($maximum) || ($len>$config[$maximum][0])) $maximum = $conf;
        }
        
        $len = ($curLen+$boardGap+$boardB1);
        $conf = $currConf.'-B1';
        if ($len < $maxlen) {
            $config[$conf] = array ($len, $conf, false);
            //if (empty($maximum) || ($len>$config[$maximum][0])) $maximum = $conf;
        }
        
        $len = ($curLen+$boardGap+$boardB1+$boardGap+$boardC);
        $conf = $currConf.'-B1-C';
        if ($len < $maxlen) {
            $config[$conf] = array ($len, $conf, true);
            if (empty($maximum) || ($len>$config[$maximum][0])) $maximum = $conf;
        }
        
        $len = ($curLen+$boardGap+$boardB1+$boardGap+$boardB1);
        $conf = $currConf.'-B1-B1';
        if ($len < $maxlen) {
            $config[$conf] = array ($len, $conf, false);
            //if (empty($maximum) || ($len>$config[$maximum][0])) $maximum = $conf;
        }
        
        $len = ($curLen+$boardGap+$boardB1+$boardGap+$boardB1+$boardGap+$boardC);
        $conf = $currConf.'-B1-B1-C';
        if ($len < $maxlen) {
            $config[$conf] = array ($len, $conf, true);
            if (empty($maximum) || ($len>$config[$maximum][0])) $maximum = $conf;
        }
        
        $len = $curLen+$boardGap+$boardLen;
        if ($len < $maxlen) {
            $currConf = $currConf.'-'.$boardName;
            $config[$currConf] = array ($len, $currConf, false);
            //if (empty($maximum) || ($len>$config[$maximum][0])) $maximum = $currConf;
            $this->architecturalIterate($len, $currConf, $boardLen, $boardName, $boards, $maxlen, $boardGap, $boardC, $boardB1, $config, $maximum);
        } 
        
        
    }
    
    function architecturalFindLength($configs, $MAXLEN, $configuration, $cLen, $iteration, &$csetup) {
        if ($iteration>=4) {
            return;
        }
        
        foreach ($configs as $type=>$config) {
            // if this is a linkable component
            if (($cLen+$config[0])>$MAXLEN) {
                continue;
            }
            
            $conf = $configuration;
            
            if (isset($conf[$type])) {
                $conf[$type]+=1;
            } else {
                $conf[$type]=1;
            }
            
            $csetup[] = array(
                $cLen+$config[0],
                $conf,
                $iteration+1
            );
            
            if ($config[2]===true) {
                $this->architecturalFindLength($configs, $MAXLEN, $conf, $cLen+$config[0], $iteration+1, $csetup);
            } 
            
        }
    }
    
}
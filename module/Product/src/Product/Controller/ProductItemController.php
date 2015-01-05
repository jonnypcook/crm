<?php
namespace Product\Controller;

// Authentication with Remember Me
// http://samsonasik.wordpress.com/2012/10/23/zend-framework-2-create-login-authentication-using-authenticationservice-with-rememberme/

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Zend\Json\Json;

use Application\Entity\User; 
use Application\Controller\AuthController;

use Zend\Mvc\MvcEvent;

use Zend\View\Model\JsonModel;

class ProductitemController extends AuthController
{
    /**
     * product item
     * @var \Product\Entity\Product 
     */
    protected $product;


    public function onDispatch(MvcEvent $e) {
        $pid = (int) $this->params()->fromRoute('pid', 0);
        $product = $this->getEntityManager()->find('\Product\Entity\Product', $pid);
        
        $this->setProduct($product);
        $this->getView()->setVariable('product', $product);
        
        $this->amendNavigation();
        
        return parent::onDispatch($e);
    }
    
    public function getProduct() {
        return $this->product;
    }

    public function setProduct(\Product\Entity\Product $product) {
        $this->product = $product;
        return $this;
    }

        
    public function indexAction()
    {
        $this->setCaption($this->getProduct()->getModel());
		return $this->getView();
    }

    public function setupAction() {
        $form = new \Product\Form\ProductConfigForm($this->getEntityManager(), array('itemMode'=>true));
        $form->setBindOnValidate(true);
        $form->bind($this->getProduct());
        
        if ($this->getRequest()->isXmlHttpRequest()) {
            try{
                $post = $this->getRequest()->getPost();
                $form->setData($post);
                
                if ($form->isValid()) {
                    $form->bindValues();
                    $this->getEntityManager()->flush();
                    $data = array('err'=>false);
                    $this->AuditPlugin()->audit(323, $this->getUser()->getUserId(), array(
                        'product'=>$this->getProduct()->getProductId()
                    ));
                }else {
                    $data = array('err'=>true, 'info'=>$form->getMessages());
                }/**/
            } catch (\Exception $ex) {
                $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
            }
            return new JsonModel(empty($data)?array('err'=>true):$data);/**/
        } else {
            $form->setAttribute('action', '/product-'.$this->getProduct()->getProductId().'/setup/')
                ->setAttribute('class', 'form-horizontal');
            return new ViewModel(array(
                'error' => 'Your authentication credentials are not valid',
                'form'	=> $form,
                'messages' => $messages,
            ));
        }
    }
    
    
    public function amendNavigation() {
        // check current location
        $action = $this->params('action');
        
        
        // get client
        $product = $this->getProduct();
        
        // grab navigation object
        $navigation = $this->getServiceLocator()->get('navigation');

        $navigation->addPage(array(
            'type' => 'uri',
            'active'=>true,  
            'ico'=> 'icon-tags',
            'order'=>1,
            'uri'=> '/product/catalog/',
            'label' => 'Products',
            'skip' => true,
            'pages' => array(
                array (
                    'type' => 'uri',
                    'active'=>true,  
                    'ico'=> 'icon-tag',
                    'order'=>1,
                    'uri'=> '/product-'.$product->getProductId().'/',
                    'label' => $product->getModel(),
                    'mlabel' => 'Product #'.str_pad($product->getProductId(), 5, "0", STR_PAD_LEFT),
                    'pages' => array(
                        array(
                            'label' => 'Dashboard',
                            'active'=>($action=='index'),  
                            'uri' => '/product-'.$product->getProductId().'/',
                            'title' => ucwords($product->getModel()).' Overview',
                        ),
                        array(
                            'active'=>($action=='setup'),  
                            'label' => 'Configuration',
                            'uri' => '/product-'.$product->getProductId().'/setup/',
                            'title' => ucwords($product->getModel()).' Configuration',
                        ),
                        array(
                            'active'=>($action=='bom'),  
                            'label' => 'BOM Setup',
                            'uri' => '/product-'.$product->getProductId().'/bom/',
                            'title' => ucwords($product->getModel()).' BOM Configuration',
                        ),
                        array(
                            'active'=>($action=='documents'),  
                            'label' => 'Documents',
                            'uri' => '/product-'.$product->getProductId().'/documents/',
                            'title' => ucwords($product->getModel()).' Documents',
                        ),
                        array(
                            'active'=>($action=='images'),  
                            'label' => 'Image Gallery',
                            'uri' => '/product-'.$product->getProductId().'/images/',
                            'title' => ucwords($product->getModel()).' Image Gallery',
                        ),
                    )
                )
            )
        ));
        
     
    }

   
}
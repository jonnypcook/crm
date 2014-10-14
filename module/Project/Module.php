<?php
namespace Project;

use Project\Controller\ProjectitemdocumentController;
use Project\Service\DocumentService;

class Module
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
    
     
    public function getControllerConfig()
    {
        return array(
          'factories' => array(
            'Project\Controller\ProjectItemDocumentController' => function(\Zend\Mvc\Controller\ControllerManager $cm) {
                return new \Project\Controller\ProjectitemdocumentController($cm->getServiceLocator()->get('DocumentService'));
              },
            ),
          );
    }
    
    
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Model' => 'Project\Factory\ModelFactory',
                'DocumentService' => function($sm) {
                    $config = $sm->get('Config');
                    return new DocumentService($config['googleApps']['drive']['location'], $sm->get('Doctrine\ORM\EntityManager'));
                }
            ),
            
        );
    }
    
    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'wordify' => function($sm) {
                    $helper = new Helper\Wordify;
                    return $helper;
                }
            )
        );   
   }
}

<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Report\Controller\Report' => 'Report\Controller\ReportController',
        ),
    ),
    
    'doctrine' => array(
        'driver' => array(
          'application_entities' => array(
            'class' =>'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
            'cache' => 'array',
            'paths' => array(__DIR__ . '/../src/Report/Entity')
          ),

          'orm_default' => array(
            'drivers' => array(
              'Report\Entity' => 'application_entities'
            )
     ))), 
    
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
           'reports' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/report[/:action[/:group/:report[/]]][/]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                        'controller' => 'Report\Controller\Report',
                        'action'     => 'index',
                    ),
                ),
            ),             
        ),
     ),
    
    'view_manager' => array(
        'template_path_stack' => array(
            'report' => __DIR__ . '/../view',
        ),
    ),
);
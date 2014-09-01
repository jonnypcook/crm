<?php
return array(
    /*'controllers' => array(
         'invokables' => array(
             'Client\Controller\Client' => 'Client\Controller\ClientController',
         ),
     ),/**/
    
    'doctrine' => array(
        'driver' => array(
          'application_entities' => array(
            'class' =>'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
            'cache' => 'array',
            'paths' => array(__DIR__ . '/../src/Contact/Entity')
          ),

          'orm_default' => array(
            'drivers' => array(
              'Contact\Entity' => 'application_entities'
            )
     ))), 


    // The following section is new and should be added to your file
     /*'router' => array(
         'routes' => array(
            'clients' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/client[/][:action[/]]',
                     'constraints' => array(
                         'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         'controller' => 'Client\Controller\Clients',
                         'action'     => 'index',
                     ),
                 ),
             ),             
             'client' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/client-[:id][/][:action[/]]',
                     'constraints' => array(
                         'id'     => '[0-9]+',
                         'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         'controller' => 'Client\Controller\Client',
                         'action'     => 'index',
                     ),
                 ),
             ),
         ),
     ),/**/
    
    'view_manager' => array(
       'template_path_stack' => array(
            'contact' => __DIR__ . '/../view',
        ),
    ),
    
);
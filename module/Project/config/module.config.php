<?php
return array(
    'controllers' => array(
         'invokables' => array(
             'Project\Controller\Projects' => 'Project\Controller\ProjectController',
             'Project\Controller\ProjectItem' => 'Project\Controller\ProjectItemController',
             'Project\Controller\ProjectItemDocument' => 'Project\Controller\ProjectItemDocumentController',
         ),
     ),

    'doctrine' => array(
        'driver' => array(
          'application_entities' => array(
            'class' =>'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
            'cache' => 'array',
            'paths' => array(__DIR__ . '/../src/Project/Entity')
          ),

          'orm_default' => array(
            'drivers' => array(
              'Project\Entity' => 'application_entities'
            )
     ))), 
    
    // The following section is new and should be added to your file
     'router' => array(
         'routes' => array(
            'projects' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/project[/][:action[/]]',
                     'constraints' => array(
                         'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         'controller' => 'Project\Controller\Projects',
                         'action'     => 'index',
                     ),
                 ),
             ),             
             'projectdocument' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/client-:cid/project-:pid/document[/]:action[/]',
                     'constraints' => array(
                         'cid'     => '[0-9]+',
                         'pid'     => '[0-9]+',
                         'action' => '[a-zA-Z][a-zA-Z0-9_]*',
                     ),
                     'defaults' => array(
                         'controller' => 'Project\Controller\ProjectItemDocument',
                         'action'     => 'index',
                     ),
                 ),
             ),
             'project' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/client-:cid/project-:pid[/][:action[/]]',
                     'constraints' => array(
                         'cid'     => '[0-9]+',
                         'pid'     => '[0-9]+',
                         'action' => '[a-zA-Z][a-zA-Z0-9_]*',
                     ),
                     'defaults' => array(
                         'controller' => 'Project\Controller\ProjectItem',
                         'action'     => 'index',
                     ),
                 ),
             ),
         ),
     ),
    
    'view_manager' => array(
       'template_path_stack' => array(
            'project' => __DIR__ . '/../view',
        ),
    ),
    
);
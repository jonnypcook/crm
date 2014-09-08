<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'User\Controller\User' => 'User\Controller\UserController',
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'user' => __DIR__ . '/../view',
        ),
    ),
    
    // The following section is new and should be added to your file
     'router' => array(
         'routes' => array(
             'user' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/user[/][:action[/]]',
                     'constraints' => array(
                         'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         'controller' => 'User\Controller\User',
                         'action'     => 'index',
                     ),
                 ),
             ),
         ),
     ),
);
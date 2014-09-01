<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Login\Controller\Doctrine' => 'Login\Controller\DoctrineController',
            'Login\Controller\Google' => 'Login\Controller\GoogleController',
        ),
    ),
    'router' => array (
        'routes' => array (
            'login' => array (
                'type' => 'Literal',
                'options' => array (
                    'route' => '/login',
                    'defaults' => array (
                        '__NAMESPACE__' => 'Login\Controller',
                        'controller' => 'Doctrine',
                        'action' => 'index'
                    ),
                ),
                'may_terminate' => true
            ),
            'logout' => array (
                'type' => 'Literal',
                'options' => array (
                    'route' => '/logout',
                    'defaults' => array (
                        '__NAMESPACE__' => 'Login\Controller',
                        'controller' => 'Doctrine',
                        'action' => 'logout'
                    ),
                ),
                'may_terminate' => true
            )
        )
    ),
    'view_manager' => array(
        'template_map' => array(
            'login/layout'           => __DIR__ . '/../view/layout/layout.phtml',
        ),/**/
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
        'display_exceptions'       => true,
    ),
);
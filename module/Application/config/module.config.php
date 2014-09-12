<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'controller_plugins' => array(
            'invokables' => array(
              'auditPlugin' => 'Application\Plugin\AuditPlugin',
              'debug' => 'Application\Plugin\DebugPlugin',
        )
    ),
    'doctrine' => array(
        'driver' => array(
          'application_entities' => array(
            'class' =>'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
            'cache' => 'array',
            'paths' => array(__DIR__ . '/../src/Application/Entity')
          ),

          'orm_default' => array(
            'drivers' => array(
              'Application\Entity' => 'application_entities'
            )
     ))), 
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Dashboard',
                        'action'     => 'index',
                    ),
                ),
            ),
            'dashboard' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/dashboard/:action[/]',
                     'constraints' => array(
                         'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         'controller' => 'Application\Controller\Dashboard',
                     ),
                 ),
             ),             

            'calendar' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/calendar/:action[/]',
                     'constraints' => array(
                         'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         'controller' => 'Application\Controller\Calendar',
                         'action'   => 'index'
                     ),
                 ),
             ),             

            'search' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/search[/:action][/]',
                     'constraints' => array(
                         'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         'controller' => 'Application\Controller\Search',
                         'action'   => 'index'
                     ),
                 ),
             ),             

            // The following is a route to simplify getting started creating
            // new controllers and actions without needing to create a new
            // module. Simply drop new controllers in, and you can access them
            // using the path /application/:controller/:action
            
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
        'factories' => array(
            'navigation' => 'Zend\Navigation\Service\DefaultNavigationFactory', // <-- add this
        ),
    ),
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'Application\Controller\Dashboard' => 'Application\Controller\DashboardController',
            'Application\Controller\Calendar' => 'Application\Controller\CalendarController',
            'Application\Controller\Search' => 'Application\Controller\SearchController',
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/dashboard/index' => __DIR__ . '/../view/application/dashboard/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
            ),
        ),
    ),
    // create the navigation
    'navigation' => array(
        'default' => array(
            array(
                'label' => 'Dashboard',
                'route' => 'home',
                'ico'=> 'icon-dashboard',
            ),
            array(
                'label' => 'Clients',
                'route' => 'clients',
                'ico'=> 'icon-book',
                'pages' => array(
                    array(
                        'label' => 'Clients',
                        'route' => 'clients',
                    ),
                    array(
                        'label' => 'Projects',
                        'route' => 'projects',
                    ),
                    array(
                        'label' => 'Jobs',
                        'route' => 'login',
                    ),
                ),
            ),
            array(
                'label' => 'Products',
                'route' => 'product',
                'ico'=> 'icon-tags',
                'pages' => array(
                    array(
                        'label' => 'Catalogue',
                        'route' => 'product',
                        'action' => 'catalog',
                    ),
                    array(
                        'label' => 'Add New Item',
                        'route' => 'product',
                        'action'=>'add'
                    ),
                    array(
                        'label' => 'Reporting',
                        'route' => 'product',
                        'action'=>'reporting'
                    ),
                ),
            ),
            array(
                'label' => 'Legacy',
                'route' => 'legacy',
                'ico'=> 'icon-undo',
                'pages' => array(
                    array(
                        'label' => 'Catalogue',
                        'route' => 'legacy',
                        'action'=>'catalog'
                    ),
                    array(
                        'label' => 'Add New Item',
                        'route' => 'legacy',
                        'action'=>'add'
                    ),
                ),
            ),
            array(
                'label' => 'Contacts',
                'route' => 'login',
                'ico'=> 'icon-book',
            ),
            array(
                'label' => 'Reporting',
                'route' => 'login',
                'ico'=> 'icon-th',
                'pages' => array(
                    array(
                        'label' => 'User Reports',
                        'route' => 'home',
                        'controller' => 'client',
                        'action' => 'id',
                    ),
                    array(
                        'label' => 'Product Reports',
                        'route' => 'login',
                    ),
                    array(
                        'label' => 'General Reports',
                        'route' => 'login',
                    ),
                ),
            ),
            array(
                'label' => 'Tracking',
                'route' => 'login',
                'ico'=> 'icon-fire',
            ),
            array(
                'label' => 'Calendar',
                'route' => 'calendar',
                'ico'=> 'icon-calendar',
            ),
            array(
                'label' => 'Search',
                'route' => 'search',
                'ico'=> 'icon-search',
            ),
            array(
                'label' => 'Administration',
                'route' => 'login',
                'ico'=> 'icon-cog',
                'pages' => array(
                    array(
                        'label' => 'User Profile',
                        'route' => 'user',
                        'action' => 'profile'
                    ),
                    array(
                        'label' => 'Change Password',
                        'route' => 'user',
                        'action' => 'password'
                    ),
                ),
            ),
            array(
                'label' => 'Logout',
                'route' => 'logout',
                'ico'=> 'icon-user',
            ),
        ),
    ),    
);

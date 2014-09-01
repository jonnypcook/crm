<?php
return array(
    'controllers' => array(
         'invokables' => array(
             'Product\Controller\Legacy' => 'Product\Controller\LegacyController',
             'Product\Controller\Product' => 'Product\Controller\ProductController',
             'Product\Controller\Products' => 'Product\Controller\ProductsController',
         ),
     ),
    
    'doctrine' => array(
        'driver' => array(
          'application_entities' => array(
            'class' =>'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
            'cache' => 'array',
            'paths' => array(__DIR__ . '/../src/Product/Entity')
          ),

          'orm_default' => array(
            'drivers' => array(
              'Product\Entity' => 'application_entities'
            )
     ))), 

    // The following section is new and should be added to your file
     'router' => array(
         'routes' => array(
            'legacy' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/legacy[/][:action[/]]',
                     'constraints' => array(
                         'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         'controller' => 'Product\Controller\Legacy',
                         'action'     => 'catalog',
                     ),
                 ),
             ),             
            'product' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/product[/][:action[/]]',
                     'constraints' => array(
                         'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         'controller' => 'Product\Controller\Product',
                         'action'     => 'catalog',
                     ),
                 ),
             ),             
             'productitem' => array(
                 'type'    => 'segment',
                 'options' => array(
                     'route'    => '/product-[:id][/][:action[/]]',
                     'constraints' => array(
                         'id'     => '[0-9]+',
                         'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                     ),
                     'defaults' => array(
                         'controller' => 'Product\Controller\Product',
                         'action'     => 'index',
                     ),
                 ),
             ),
         ),
     ),
    
    'view_manager' => array(
       'template_path_stack' => array(
            'product' => __DIR__ . '/../view',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
    
);
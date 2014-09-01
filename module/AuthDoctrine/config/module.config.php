<?php
namespace AuthDoctrine;

return array(
    'controllers' => array(
        'invokables' => array(
            'AuthDoctrine\Controller\Index' => 'AuthDoctrine\Controller\IndexController'
        ),
    ),
    'router' => array (
        'routes' => array (
            'auth-doctrine' => array (
                'type' => 'Literal',
                'options' => array (
                    'route' => '/auth-doctrine',
                    'defaults' => array (
                        '__NAMESPACE__' => 'AuthDoctrine\Controller',
                        'controller' => 'Index',
                        'action' => 'index'
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array (
                    'default' => array (
                        'type' => 'Segment',
                        'options' => array (
                            'route' => '/[:controller[/:action[/:id]]]',
                            'constraints' => array (
                                'controller'=> '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array (
                                
                            )
                        )
                    )
                )
            )
        )
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
        'display_exceptions'       => true,
    ),
    
    'doctrine' => array(
		// 1) for Aithentication
        'authentication' => array( // this part is for the Auth adapter from DoctrineModule/Authentication
            'orm_default' => array(
                'object_manager' => 'Doctrine\ORM\EntityManager',
				// object_repository can be used instead of the object_manager key
                'identity_class' => '\Application\Entity\User', //'Application\Entity\User',
                'identity_property' => 'username', // 'username', // 'email',
                'credential_property' => 'password', // 'password',
                'credential_callable' => function(\Application\Entity\User $user, $passwordGiven) { // not only User
                    // return my_awesome_check_test($user->getPassword(), $passwordGiven);
					// echo '<h1>callback user->getPassword = ' .$user->getPassword() . ' passwordGiven = ' . $passwordGiven . '</h1>';
					//- if ($user->getPassword() == md5($passwordGiven)) { // original
					// ToDo find a way to access the Service Manager and get the static salt from config array
					if ($user->getPassword() == md5('aFGQ475SDsdfsaf2342' . $passwordGiven . $user->getPasswordSalt()) &&
						$user->getActive() == 1) {
						return true;
					}
					else {
						return false;
					}
                },
            ),
        ),
    ),
);
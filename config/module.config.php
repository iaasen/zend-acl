<?php
use Acl\Controller\AuthController;
use Acl\Controller\UserController;

return [
	'acl' => [
		'superuser_only' => false,
	],
	'service_manager' => [
		'invokables' => [
			\Acl\Form\LoginForm::class => \Acl\Form\LoginForm::class,
			\Acl\Model\Access::class => \Acl\Model\Access::class,
			\Acl\Model\Group::class => \Acl\Model\Group::class,
			\Acl\Model\User::class => \Acl\Model\User::class,
		],
		'factories' => [
			\Acl\Model\AclStorage::class => \Acl\Model\AclStorageFactory::class,
			\Acl\Service\AccessTable::class => \Acl\Service\AccessTableFactory::class,
			\Acl\Service\AuthService::class => \Acl\Service\AuthServiceFactory::class,
			\Acl\Service\GroupTable::class => \Acl\Service\GroupTableFactory::class,
			\Acl\Service\UserService::class => \Acl\Service\UserServiceFactory::class,
			\Acl\Service\UserTable::class => \Acl\Service\UserTableFactory::class,
		],
		'aliases' => [
			'Acl\AuthService' => \Acl\Service\AuthService::class,
			'Acl\Model\AclTable' => \Acl\Service\UserTable::class,
			'AuthService'	=> \Acl\Service\AuthService::class ,
			'Group' => \Acl\Model\Group::class,
			'GroupTable' => \Acl\Service\GroupTable::class,
			'User' => \Acl\Model\User::class,
			'UserTable' => \Acl\Service\UserTable::class,
			\Zend\Authentication\AuthenticationService::class => \Acl\Service\AuthService::class,
		],
	],
	'controllers' => [
		'invokables' => [
		],
		'factories' => [
			AuthController::class => \Acl\Controller\AuthControllerFactory::class,
			UserController::class => \Acl\Controller\UserControllerFactory::class,
		],
	],
	'view_helpers' => [
		'aliases' => [
			\Zend\Authentication\AuthenticationService::class => \Acl\Service\AuthService::class,
			'groupselection' => \Acl\View\Helper\GroupSelectionWidget::class,
		],
		'factories' => [
			\Acl\View\Helper\GroupSelectionWidget::class => \Acl\View\Helper\GroupSelectionWidgetFactory::class,
		],
	],
	'settings' => [
		'modules' => [
			'group' => [
				'label' => 'Firma',
				'fields' => [
					'name' => [
						'label' => 'Firmanavn',
						'type' => 'string',
						'default_value' => '',
						'access_level' => 3,
					],
					'address1' => [
						'label' => 'Adresse 1',
						'type' => 'string',
						'default_value' => '',
						'access_level' => 3,
					],
					'address2' => [
						'label' => 'Adresse 2',
						'type' => 'string',
						'default_value' => '',
						'access_level' => 3,
					],
					'postalcode' => [
						'label' => 'Postnr',
						'type' => 'string',
						'default_value' => '',
						'access_level' => 3,
					],
					'postalarea' => [
						'label' => 'Poststed',
						'type' => 'string',
						'default_value' => '',
						'access_level' => 3,
					],
					'phone' => [
						'label' => 'Kontortelefon',
						'type' => 'string',
						'default_value' => '',
						'access_level' => 3,
					],
					'email' => [
						'label' => 'E-post',
						'type' => 'string',
						'default_value' => '',
						'access_level' => 3,
					],
//					'elfag_membership_number' => [
//						'label' => 'Elfag medlemsnummer',
//						'type' => 'string',
//						'default_value' => null,
//						'access_level' => 3,
//					],
				],
			],
		], 
	],
	'router' => [
		'routes' => [
			'auth' => [
				'type'		=> 'Literal',
				'options'	=> [
					'route'		=> '/auth',
					'defaults'	=> [
						'controller'	=> AuthController::class,
						'action' => 'login',
					],
				],
				'may_terminate' => true,
				'child_routes' => [
					'login' => [
						'type' => 'literal',
						'options' => [
							'route' => '/login',
							'defaults' => [
								'action' => 'login',
							],
						],
					],
					'logout' => [
						'type' => 'literal',
						'options' => [
							'route' => '/logout',
							'defaults' => [
								'action' => 'logout',
							],
						],
					],
					'authenticate' => [
						'type' => 'literal',
						'options' => [
							'route' => '/authenticate',
							'defaults' => [
								'action' => 'authenticate',
							],
						],
					],
					'bcrypt' => [
						'type' => 'literal',
						'options' => [
							'route' => '/generatebcrypt',
							'defaults' => [
								'action' => 'generateBcrypt',
							],
						],
					],
				],
			],
			'user' => [
				'type' => 'literal',
				'options' => [
					'route' => '/bruker',
					'defaults' => [
						'controller' => UserController::class,
						'action'     => 'list',
					]
				],
				'may_terminate' => true,
				'child_routes' => [
					'list' => [
						'type' => 'literal',
						'options' => [
							'route' => '/oversikt',
							'defaults' => [
								'action'     => 'list',
							]
						],
						'may_terminate' => true,
					],
					'edit' => [
						'type'    => 'segment',
						'options' => [
							'route'    => '/rediger/:id',
							'constraints' => [
								'id'     => '[0-9]+',
							],
							'defaults' => [
								'action'     => 'edit',
							],
							'may_terminate' => true,
						],
					],
					'editAccess' => [
						'type'    => 'literal',
						'options' => [
							'route'    => '/tilgang',
							'defaults' => [
								'action'     => 'editaccess',
							],
							'may_terminate' => true,
						],
					],
					'createSoapUser' => [
						'type' => 'literal',
						'options' => [
							'route' => '/vismabruker',
							'defaults' => [
								'action' => 'createSoapUser',
							]
						]
					],
					'createElfagUser' => [
						'type' => 'literal',
						'options' => [
							'route' => '/elfagbruker',
							'defaults' => [
								'action' => 'createElfagUser',
							]
						]
					],
					'selectGroup' => [
						'type' => 'literal',
						'options' => [
							'route' => '/firmavalg',
							'defaults' => [
								'action' => 'selectGroup',
							]
						]
					]
				],
			],
		],
	],
//	'navigation' => [
//		'default' => [
//			'settings' => [
//				'label' => 'Innstillinger',
//				'route' => 'user/list',
//				'pages' => [
//					'user' => [
//						'label' => 'Brukere',
//						'route' => 'user/list',
//						'pages' => [
//							'editaccess' => [
//								'label' => 'Tilgang',
//								'route' => 'user/editaccess',
//							],
//							'createSoapUser' => [
//								'label' => 'Opprett Vismabruker',
//								'route' => 'user/createSoapUser',
//							],
//							'selectGroup' => [
//								'label' => 'Velg firma',
//								'route' => 'user/selectGroup',
//							],
//						],
//					],
//				],
//			],
//		],
//	],
	'translator' => [
		'locale' => 'nb_NO',
		'translation_file_patterns' => [
			[
				'type'     => 'gettext',
				'base_dir' => __DIR__ . '/../language',
				'pattern'  => '%s.mo',
			],
		],
	],
	'view_manager' => [
		'template_path_stack' => [
			'Acl' => __DIR__ . '/../view',
		],
	],
	'session' => [
		'config' => [
			'options' => [
				'cookie_lifetime' => 28800,
				'gc_maxlifetime' => 28800,
				'use_cookies' => true,
				'use_only_cookies' => false,
				'cookie_httponly' => false,
				'name' => 'boligkalk',
			],
		],
	],
];
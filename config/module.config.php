<?php
use Acl\Controller\AuthController;
use Acl\Controller\UserController;

return [
	'service_manager' => [
		'aliases' => [
			'AclTable'	=> 'Acl\Model\UserTable',
			'AuthService'	=> 'Acl\AuthService',
			\Zend\Authentication\AuthenticationService::class => 'Acl\AuthService',
		],
	],
	'controllers' => [
		'invokables' => [
			AuthController::class => AuthController::class,
			UserController::class => UserController::class,
		],
	],
	'view_helpers' => [
		'aliases' => [
			\Zend\Authentication\AuthenticationService::class => 'Acl\AuthService',
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
								'action' => 'generatebcrypt',
							],
						],
					],
				],
			],
			'createuser' => [
				'type'    => 'literal',
				'options' => [
					'route'    => '/createuser',
					'defaults' => [
						'controller' => 'Settings\Controller\Settings',
						'action'     => 'addelfaguser',
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
	'navigation' => [
		'default' => [
			'settings' => [
				'pages' => [
					'user' => [
						'label' => 'Brukere',
						'route' => 'user/list',
						'pages' => [
							'editaccess' => [
								'label' => 'Tilgang',
								'route' => 'user/editaccess',
							],
							'createSoapUser' => [
								'label' => 'Opprett Vismabruker',
								'route' => 'user/createSoapUser',
							],
							'selectGroup' => [
								'label' => 'Velg firma',
								'route' => 'user/selectGroup',
							],
						],
					],
				],
			],
		],
	],
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
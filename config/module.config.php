<?php
use Acl\Controller\AuthController;
use Acl\Controller\UserController;

return [
	'controllers' => [
		'invokables' => [
			AuthController::class => AuthController::class,
			UserController::class => UserController::class,
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
				'type'    => 'segment',
				'options' => [
					'route'    => '/user[/][:action][/:id]',
					'constraints' => [
						'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
						'id'     => '[0-9]+',
					],
					'defaults' => [
						'controller' => UserController::class,
						'action'     => 'list',
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
	'service_manager' => [
		'aliases' => [
			'AclTable'	=> 'Acl\Model\UserTable',
			'AuthService'	=> 'Acl\AuthService',
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
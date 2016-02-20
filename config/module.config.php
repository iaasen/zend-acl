<?php
return array(
	'controllers' => array(
		'invokables' => array(
			'Acl\Controller\Auth' 		=> 'Acl\Controller\AuthController',
			'Acl\Controller\User' 		=> 'Acl\Controller\UserController',
			//'Settings\Controller\Settings' 		=> 'Settings\Controller\SettingsController',
		),
	),
	'router' => array(
		'routes' => array(
			'authenticate' => array(
				'type'		=> 'Literal',
				'options'	=> array(
					'route'		=> '/authenticate', 
					'defaults'	=> array(
						'__NAMESPACE__'	=> 'Acl\Controller',
						'controller'	=> 'Auth',
						'action'		=> 'authenticate',
					),
				),
				'may_terminate'	=> true,
				'child_routes' => array(
					'process' => array(
						'type'		=> 'Segment',
						'options'	=> array(
							'route'			=> '/[:action]',
							'constraints'	=> array(
								'controller'	=> '[a-zA-Z][a-zA-Z0-9_-]*',
								'action'		=> '[a-zA-Z][a-zA-Z0-9_-]*',
							),
							'defaults'		=> array(
							),
						),
					),
				),
			),
			
			'bcrypt' => array(
				'type'    => 'literal',
				'options' => array(
					'route'    => '/auth/generatebcrypt',
					'defaults' => array(
						'controller' => 'Acl\Controller\Auth',
						'action'     => 'generatebcrypt',
					),
				),
			),
			'createuser' => array(
				'type'    => 'literal',
				'options' => array(
					'route'    => '/createuser',
					'defaults' => array(
						'__NAMESPACE__' => 'Settings\Controller',
						'controller' => 'Settings',
						'action'     => 'addelfaguser',
					),
				),
			),
			'user' => array(
				'type'    => 'segment',
				'options' => array(
					'route'    => '/user[/][:action][/:id]',
					'constraints' => array(
						'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
						'id'     => '[0-9]+',
					),
					'defaults' => array(
						'controller' => 'Acl\Controller\User',
						'action'     => 'list',
					),
				),
			),
			'login' => array(
				'type'		=> 'Literal',
				'options'	=> array(
					'route'		=> '/auth', 
					'defaults'	=> array(
						'__NAMESPACE__'	=> 'Acl\Controller',
						'controller'	=> 'Auth',
						'action'		=> 'login',
					),
				),
				'may_terminate'	=> true,
				'child_routes' => array(
					'process' => array(
						'type'		=> 'Segment',
						'options'	=> array(
							'route'			=> '/[:action]',
							'constraints'	=> array(
								'controller'	=> '[a-zA-Z][a-zA-Z0-9_-]*',
								'action'		=> '[a-zA-Z][a-zA-Z0-9_-]*',
							),
							'defaults'		=> array(
							),
						),
					),
				),
			),
			'logout' => array(
				'type'		=> 'Literal',
				'options'	=> array(
					'route'		=> '/auth/logout',
					'defaults'	=> array(
						'__NAMESPACE__'	=> 'Acl\Controller',
						'controller'	=> 'Auth',
						'action'		=> 'logout',
					),
				),
				'may_terminate' => true,
				'child_routes' => array(
					'process' => array(
						'type'		=> 'Segment',
						'options'	=> array(
							'route'			=> '/[:action]',
							'constraints'	=> array(
								'controller'	=> '[a-zA-Z][a-zA-Z0-9_-]*',
								'action'		=> '[a-zA-Z][a-zA-Z0-9_-]*',
							),
							'defaults'		=> array(
							),
						),
					),
				),
			),
		),
	),
	'translator' => array(
		'locale' => 'nb_NO',
		'translation_file_patterns' => array(
			array(
				'type'     => 'gettext',
				'base_dir' => __DIR__ . '/../language',
				'pattern'  => '%s.mo',
			),
		),
	),
	'service_manager' => array(
		'aliases' => array(
			'AclTable'	=> 'Acl\Model\UserTable',
			'AuthService'	=> 'Acl\AuthService',
		),
	),
	'view_manager' => array(
		'template_path_stack' => array(
			'Acl' => __DIR__ . '/../view',
		),
	),
	'session' => array(
		'config' => array(
			'options' => array(
				'cookie_lifetime' => 28800,
				'gc_maxlifetime' => 28800,
				'use_cookies' => true,
				'use_only_cookies' => false,
				'cookie_httponly' => false,
				'name' => 'boligkalk',
			),
		),
	),
);
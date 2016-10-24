<?php
namespace Acl;

use Acl\Model\User;
use Acl\Model\UserTable;
use Acl\Model\Group;
use Acl\Model\GroupTable;
use Acl\View\Helper\GroupselectionWidget;

//use Zend\Authentication\Storage;
//use Zend\Authentication\AuthenticationService;
//use Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\Feature\RowGatewayFeature;
use Zend\Db\TableGateway\TableGateway;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Session\SessionManager;
use Zend\Session\Container;
use Zend\Session\SaveHandler\DbTableGateway;
use Zend\Session\SaveHandler\DbTableGatewayOptions;
use Acl\Model\AuthService;
use Acl\Model\AuthLocalAdapter;
use Acl\Model\AuthSoapAdapter;
use Acl\Model\AuthElfagAdapter;


class Module implements AutoloaderProviderInterface {
	public function getAutoloaderConfig() {
		return array(
			'Zend\Loader\ClassMapAutoloader' => array(
				__DIR__ . '/autoload_classmap.php',
			),
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
				),
			),
		);
	}
	
	protected $whitelist = array(
		'auth/login',
		'auth/authenticate',
		'login/process/createuser',
		'login/process/generatebcrypt',
		'soap/soap',
		'soapwsdl/wsdl',
		'index/credits',
		'claim/email',
		'claimemail',
		'claim/emailFile',
	);
	
	public function onBootstrap(MvcEvent $e)
	{
		$eventManager        = $e->getApplication()->getEventManager();
		$moduleRouteListener = new ModuleRouteListener();
		$moduleRouteListener->attach($eventManager);
		$this->bootstrapSession($e);
		
		$serviceManager = $e->getApplication()->getServiceManager();
		$acl = $serviceManager->get('Acl\AuthService');
		$list = $this->whitelist;
	
		$eventManager->attach(MvcEvent::EVENT_ROUTE, function($e) use ($list, $acl) {
			/** @var \Bugsnag\Client $bugsnag */
			global $bugsnag;

			if($e->getRequest() instanceof \Zend\Console\Request) return;
			
			$match = $e->getRouteMatch();
			$redirect = $_SERVER['REQUEST_URI'];
			
			
			if(!$match instanceof RouteMatch) {
				return;
			}
				
			if($acl->hasIdentity()) {
				$bugsnag->registerCallback(function (\Bugsnag\Report $report) use ($acl) {
					$report->setUser([
						'username' => $acl->getIdentity(),
					]);
				});
				return;
			}
			
			$name = $match->getMatchedRouteName();
			$action = $match->getParam('action');
			$searchname = $name . '/' . $action;
			//~r($searchname);	
			if(in_array($name, $list) || in_array($searchname, $list)) {
				return;
			}
			
			$router = $e->getRouter();
			$url = $router->assemble(array(), array(
				'name'	=>	'auth/login',
			));
				
			$response = $e->getResponse();
			//$response->getHeaders()->addHeaderLine('Location', $url);
			$response->getHeaders()->addHeaderLine('Location', $url . '?redirect=' . $redirect);
			$response->setStatusCode(302);
			return $response;
		}, -100);
	
	}
	
	public function bootstrapSession($e)
	{
		$session = $e->getApplication()
			->getServiceManager()
			->get('Zend\Session\SessionManager');
		$session->start();
	}
	
	public function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}
	
	public function getViewHelperConfig() {
		return array(
			'factories' => array(
				'groupselection' => function ($sm) {
					$locator = $sm->getServiceLocator();
					$helper = new GroupselectionWidget();
					return $helper;
				}
			)
		);
	}
	
	
	
	public function getServiceConfig() {
		return array(
			'factories' => array(
				'Acl\Form\LoginForm' => function($sm) {
					return new \Acl\Form\LoginForm();
				},
				'Acl\Model\AclStorage'	=> function($sm) {
					return new \Acl\Model\AclStorage('acl_auth');
				},
				'Acl\Model\AclTable'	=> function($sm) {
					$_tableGateway = $sm->get('AclTableGateway');
					$_table = new UserTable($_tableGateway);
					
					return $_table;
				},
				'AclTableGateway'		=> function($sm) {
					$_dbAdapter = $sm->get('Db\Acl');
					$_resultSetPrototype = new ResultSet();
					$_resultSetPrototype->setArrayObjectPrototype(new User());
					return new TableGateway('users', $_dbAdapter, new RowGatewayFeature('id'), $_resultSetPrototype);
				},
				'User' => function($sm) {
					$model = new User();
					return $model;
				},
				'UserTable' => function ($sm) {
					$tableGateway = $sm->get('UserTableGateway');
					$table = new UserTable($tableGateway);
					$table->setGroupTable($sm->get('GroupTable'));
					return $table;
				},
				'UserTableGateway' => function ($sm) {
					$dbAdapter = $sm->get('Db\Acl');
					$resultSetPrototype = new ResultSet();
					$resultSetPrototype->setArrayObjectPrototype($sm->get('User'));
					return new TableGateway('users', $dbAdapter, null, $resultSetPrototype);
					//return new TableGateway('users', $dbAdapter, new RowGatewayFeature('id'), $resultSetPrototype);
				},
				'Group' => function($sm) {
					$model = new Group();
					return $model;
				},
				'GroupTable' => function ($sm) {
					$tableGateway = $sm->get('GroupTableGateway');
					$table = new GroupTable($tableGateway);
					return $table;
				},
				'GroupTableGateway' => function ($sm) {
					$dbAdapter = $sm->get('Db\Acl');
					$resultSetPrototype = new ResultSet();
					$resultSetPrototype->setArrayObjectPrototype($sm->get('Group'));
					return new TableGateway('groups', $dbAdapter, null, $resultSetPrototype);
				},
				'Acl\AuthService' => function($sm) {
					$dbAdapter = $sm->get('Db\Acl');
					$authLocal = $sm->get('Acl\AuthLocalAdapter');
					$authElfag = new AuthElfagAdapter();
					$authService = new AuthService();
					
					$authService->addAdapter($authLocal);
					$authService->addAdapter($authElfag);
					$authService->setStorage($sm->get('Acl\Model\AclStorage'));
					return $authService;
					/*
					$dbAdapter = $sm->get('Db\Acl');
					$dbTableAuthAdapter = new DbTableAuthAdapter($dbAdapter, 'users', 'username', 'password');
					$authService = new AuthenticationService();
					$authService->setAdapter($dbTableAuthAdapter);
					$authService->setStorage($sm->get('Acl\Model\AclStorage'));
					return $authService;
					*/
				},
				'Acl\AuthSoapService' => function($sm) {
					//$dbAdapter = $sm->get('Db\Acl');
					$authSoap = $sm->get('Acl\AuthSoapAdapter');
					$authService = new AuthService();
					$authService->addAdapter($authSoap);
					$authService->setStorage($sm->get('Acl\Model\AclStorage'));
					//$logger = new \Zend\Log\Logger;
					//$logger->addWriter('stream', null, array('stream' => 'c:\log.txt'));
					return $authService;
				},
				'Acl\AuthLocalAdapter' => function($sm) {
					return new AuthLocalAdapter($sm->get('Db\Acl'), 'users', 'username', 'password');
				},
				'Acl\AuthSoapAdapter' => function($sm) {
					return new AuthSoapAdapter($sm->get('Db\Acl'), 'users', 'username', 'password');
				},
				'Zend\Session\SessionManager' => function ($sm) {
					$conf = $sm->get('config');
					$conf = $conf['session'];
				
					$sessionConfig = null;
					if (isset($conf['config'])) {
						$options = isset($conf['config']['options']) ? $conf['config']['options'] : array();
						$sessionConfig = new \Zend\Session\Config\SessionConfig();
						$sessionConfig->setOptions($options);
					}
				
					$tableGateway = $sm->get('SessionTableGateway');
					$saveHandler = new DbTableGateway($tableGateway, new DbTableGatewayOptions());
					$sessionManager = new SessionManager($sessionConfig, null, $saveHandler);
					Container::setDefaultManager($sessionManager);
					return $sessionManager;
				},
				'SessionTableGateway' => function ($sm) {
					$dbAdapter = $sm->get('Db\Acl');
					return new TableGateway('sessions', $dbAdapter);
				},
				
			),
		);
	}
}
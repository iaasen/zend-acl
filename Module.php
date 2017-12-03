<?php
namespace Acl;

use Acl\Adapter\AuthElfag2Adapter;
use Acl\Service\Elfag2Service;
use Zend\Db\TableGateway\TableGateway;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Router\Http\RouteMatch;
use Zend\ServiceManager\ServiceManager;
use Zend\Session\SessionManager;
use Zend\Session\Container;
use Zend\Session\SaveHandler\DbTableGateway;
use Zend\Session\SaveHandler\DbTableGatewayOptions;


class Module implements AutoloaderProviderInterface {
	public function getAutoloaderConfig() {
		return array(
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

		$this->elfag2UserLoggedInEvent($eventManager, $serviceManager);
	
		$eventManager->attach(MvcEvent::EVENT_ROUTE, function(MvcEvent $e) use ($list, $acl) {
			/** @var \Bugsnag\Client $bugsnag */
			global $bugsnag;

			if($e->getRequest() instanceof \Zend\Console\Request) return;
			
			$match = $e->getRouteMatch();

			$redirect = $_SERVER['REQUEST_URI'];

			if(!$match instanceof RouteMatch) {
				return;
			}

			if($acl->hasIdentity()) {
				if($bugsnag) {
					$bugsnag->registerCallback(function (\Bugsnag\Report $report) use ($acl) {
						$report->setUser([
							'username' => $acl->getIdentity(),
						]);
					});
				}
				return;
			}
			$name = $match->getMatchedRouteName();
			$action = $match->getParam('action');
			$searchName = $name . '/' . $action;
			//~r($searchname);
			if(in_array($name, $list) || in_array($searchName, $list)) {
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

	/**
	 * @param EventManager $eventManager
	 * @param ServiceManager $serviceManager
	 */
	protected function elfag2UserLoggedInEvent($eventManager, $serviceManager) {
		$eventManager->getSharedManager()->attach(
			AuthElfag2Adapter::class,
			'user_elfag2_logged_in',
			function(Event $e) use ($serviceManager) {
				$elfag2Service = $serviceManager->get(Elfag2Service::class);
				$elfag2Service->createUserIfNeeded($e->getParam('tokenData'));
			}
		);
	}
	
	public function bootstrapSession(MvcEvent $e)
	{
		/** @var \Zend\Session\SessionManager $session */
		$session = $e->getApplication()
			->getServiceManager()
			->get('Zend\Session\SessionManager');
		$session->start();
	}
	
	public function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}
	
	public function getServiceConfig() {
		return array(
			'factories' => array(
//				'Acl\AuthSoapService' => function($sm) {
//					//$dbAdapter = $sm->get('Db\Acl');
//					$authSoap = $sm->get('Acl\AuthSoapAdapter');
//					$authService = new AuthService();
//					$authService->addAdapter($authSoap);
//					$authService->setStorage($sm->get('Acl\Model\AclStorage'));
//					//$logger = new \Zend\Log\Logger;
//					//$logger->addWriter('stream', null, array('stream' => 'c:\log.txt'));
//					return $authService;
//				},
//				'Acl\AuthLocalAdapter' => function($sm) {
//					return new AuthLocalAdapter($sm->get('Db\Acl'), 'users', 'username', 'password');
//				},
//				'Acl\AuthSoapAdapter' => function($sm) {
//					return new AuthSoapAdapter($sm->get('Db\Acl'), 'users', 'username', 'password');
//				},
				'Zend\Session\SessionManager' => function ($sm) {
					$conf = $sm->get('config');
					$conf = $conf['session'];
				
					$sessionConfig = null;
					if (isset($conf['config'])) {
						$options = isset($conf['config']['options']) ? $conf['config']['options'] : array();
						$sessionConfig = new \Zend\Session\Config\SessionConfig();
						$sessionConfig->setOptions($options);
					}
				
					//$tableGateway = $sm->get('SessionTableGateway');
					$dbAdapter = $sm->get('Db\Acl');
					$tableGateway = new TableGateway('sessions', $dbAdapter);
					$saveHandler = new DbTableGateway($tableGateway, new DbTableGatewayOptions());
					$sessionManager = new SessionManager($sessionConfig, null, $saveHandler);
					Container::setDefaultManager($sessionManager);
					return $sessionManager;
				},
//				'SessionTableGateway' => function ($sm) {
//					$dbAdapter = $sm->get('Db\Acl');
//					return new TableGateway('sessions', $dbAdapter);
//				},
				
			),
		);
	}
}
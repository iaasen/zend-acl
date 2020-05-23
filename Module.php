<?php
namespace Acl;

use Acl\Adapter\AuthElfag2Adapter;
use Acl\Service\AuthService;
use Acl\Service\Elfag2Service;
use Acl\Service\UserService;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\EventManager\Event;
use Laminas\EventManager\EventManager;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\ModuleManager\Feature\AutoloaderProviderInterface;
use Laminas\Mvc\ModuleRouteListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\SessionManager;
use Laminas\Session\Container;
use Laminas\Session\SaveHandler\DbTableGateway;
use Laminas\Session\SaveHandler\DbTableGatewayOptions;


class Module implements AutoloaderProviderInterface {
	public function getAutoloaderConfig() {
		return [
			\Laminas\Loader\StandardAutoloader::class => [
				'namespaces' => [
					__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
				],
			],
		];
	}
	
	protected $whitelist = [
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
	];

	protected $authenticatedWhitelist = [
		'user/noAccess',
		'user/create-elfag2-group',
		'user/missing-elfag2-group',
		'user/selectGroup',
	];
	
	public function onBootstrap(MvcEvent $e)
	{
		/** @var EventManager $eventManager */
		$eventManager        = $e->getApplication()->getEventManager();
		$moduleRouteListener = new ModuleRouteListener();
		$moduleRouteListener->attach($eventManager);
		$this->bootstrapSession($e);

		/** @var ServiceManager $serviceManager */
		$serviceManager = $e->getApplication()->getServiceManager();
		/** @var AuthService $authService */
		$authService = $serviceManager->get(\Acl\Service\AuthService::class);
		/** @var UserService $userService */
		$userService = $serviceManager->get(\Acl\Service\UserService::class);
		$whitelist = $this->whitelist;
		$authenticatedWhitelist = $this->authenticatedWhitelist;

		$this->elfag2UserLoggedInEvent($eventManager, $serviceManager);
	
		$eventManager->attach(MvcEvent::EVENT_ROUTE, function(MvcEvent $e) use ($whitelist, $authenticatedWhitelist, $authService, $userService) {
			/** @var \Bugsnag\Client $bugsnag */
			global $bugsnag;

			// Always give console users access
			if($e->getRequest() instanceof \Laminas\Console\Request) return true;
			
			// Can't remember what this one is for
			if(!$e->getRouteMatch() instanceof RouteMatch) return true;

			$redirect = $_SERVER['REQUEST_URI'];

			// Logged in user
			if($authService->hasIdentity()) {
				if($bugsnag) {
					$bugsnag->registerCallback(function (\Bugsnag\Report $report) use ($authService) {
						$report->setUser([
							'username' => $authService->getIdentity(),
						]);
					});
				}

				// Give access if called page is in the whitelist for authenticated users without authorization
				if($this->checkWhitelist($e, $authenticatedWhitelist)) return true;

				$currentUser = $userService->getCurrentUser();

				// No access to any group
				if(!$currentUser->countGroupAccesses()) {
					return $this->createRedirectResponse($e, 'user/noAccess', $redirect);
				}

				// Current group is set and user have access
				if($currentUser->current_group && $currentUser->getAccessLevel()) return true;
				// Have access to at least one group
				else return $this->createRedirectResponse($e, 'user/selectGroup', $redirect);
			}

			// Give access if called page is in the whitelist
			if($this->checkWhitelist($e, $whitelist)) return true;

			// Send user to login screen
			return $this->createRedirectResponse($e, 'auth/login', $redirect);

		}, -100);

	}

	// Check if request is in the whitelist
	protected function checkWhitelist(MvcEvent $event, array $whitelist) : bool {
		$match = $event->getRouteMatch();
		$name = $match->getMatchedRouteName();
		$action = $match->getParam('action');
		$searchName = $name . '/' . $action;
		if(in_array($name, $whitelist) || in_array($searchName, $whitelist)) return true;
		else return false;
	}

	protected function createRedirectResponse(MvcEvent $event, string $newRoute, string $originalRoute) : string {
		$url = $event->getRouter()->assemble([], ['name' => $newRoute]);
		/** @var Response $response */
		$response = $event->getResponse();
		$response->getHeaders()->addHeaderLine('Location', $url . '?redirect=' . $originalRoute);
		$response->setStatusCode(302);
		return $response;
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
				/** @var Elfag2Service $elfag2Service */
				$elfag2Service = $serviceManager->get(Elfag2Service::class);
				$elfag2Service->createUserIfNeeded($e->getParam('tokenData'));
			}
		);
	}
	
	public function bootstrapSession(MvcEvent $e)
	{
		/** @var \Laminas\Session\SessionManager $session */
		$session = $e->getApplication()
			->getServiceManager()
			->get(\Laminas\Session\SessionManager::class);
		$session->start();
	}
	
	public function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}
	
	public function getServiceConfig() {
		return [
			'factories' => [
				\Laminas\Session\SessionManager::class => function (ServiceManager $sm) {
					$conf = $sm->get('config');
					$conf = $conf['session'];
				
					$sessionConfig = null;
					if (isset($conf['config'])) {
						$options = isset($conf['config']['options']) ? $conf['config']['options'] : [];
						$sessionConfig = new \Laminas\Session\Config\SessionConfig();
						$sessionConfig->setOptions($options);
					}
				
					$dbAdapter = $sm->get('Db\Acl');
					$tableGateway = new TableGateway('sessions', $dbAdapter);
					$saveHandler = new DbTableGateway($tableGateway, new DbTableGatewayOptions());
					$sessionManager = new SessionManager($sessionConfig, null, $saveHandler);
					Container::setDefaultManager($sessionManager);
					return $sessionManager;
				},
			],
		];
	}
}